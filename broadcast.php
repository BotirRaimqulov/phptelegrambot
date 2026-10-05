<?php

require_once __DIR__ . '/telegram.php';
require_once __DIR__ . '/storage.php';

class Broadcaster
{
    protected $store;
    protected $telegram;
    protected $opts;

    public function __construct(Storage $store, $telegram, array $opts = [])
    {
        $this->store = $store;
        $this->telegram = $telegram;
        $this->opts = $opts + [
            'rate' => 20,
            'group_interval' => 6.0,
            'max_runtime' => 50,
            'max_attempts' => 5,
            'batch' => 200,
        ];
    }

    protected function now()
    {
        return microtime(true);
    }

    protected function sleep($seconds)
    {
        if ($seconds > 0) {
            usleep((int)($seconds * 1000000));
        }
    }

    public function enqueue(array $spec, array $chatIds)
    {
        $pdo = $this->store->pdo;
        $pdo->beginTransaction();
        $pdo->prepare('INSERT INTO broadcasts (method, from_chat_id, message_id, text, parse_mode, created_at) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute([
                $spec['method'],
                $spec['from_chat_id'] ?? null,
                $spec['message_id'] ?? null,
                $spec['text'] ?? null,
                $spec['parse_mode'] ?? null,
                time(),
            ]);
        $id = (int)$pdo->lastInsertId();

        $ins = $pdo->prepare('INSERT OR IGNORE INTO targets (broadcast_id, chat_id) VALUES (?, ?)');
        foreach (array_unique($chatIds) as $chatId) {
            $ins->execute([$id, $chatId]);
        }
        $pdo->commit();

        return $id;
    }

    public function run()
    {
        $deadline = $this->now() + $this->opts['max_runtime'];
        $slot = $this->now();
        $step = 1 / max(0.1, $this->opts['rate']);
        $lastSent = [];
        $stats = ['sent' => 0, 'blocked' => 0, 'failed' => 0, 'retry' => 0, 'paused' => 0];

        while ($this->now() < $deadline) {
            $pausedUntil = (float)$this->store->getMeta('paused_until', 0);
            if ($pausedUntil > $this->now()) {
                if ($pausedUntil >= $deadline) {
                    break;
                }
                $this->sleep($pausedUntil - $this->now());
                continue;
            }

            $batch = $this->dueTargets();
            if (!$batch) {
                break;
            }

            $progress = false;
            foreach ($batch as $t) {
                if ($this->now() >= $deadline) {
                    break 2;
                }
                $chatId = (int)$t['chat_id'];
                if ($chatId < 0 && isset($lastSent[$chatId])
                    && $this->now() - $lastSent[$chatId] < $this->opts['group_interval']) {
                    continue;
                }

                $this->sleep($slot - $this->now());
                $slot = max($slot, $this->now()) + $step;
                $progress = true;

                $outcome = $this->deliver($t);
                $stats[$outcome]++;
                if ($outcome === 'sent') {
                    $lastSent[$chatId] = $this->now();
                }
                if ($outcome === 'paused') {
                    break;
                }
            }

            if (!$progress) {
                $this->sleep(0.5);
            }
        }

        $this->store->pdo->exec('
            UPDATE broadcasts SET status = \'done\'
            WHERE status = \'running\'
              AND NOT EXISTS (SELECT 1 FROM targets WHERE broadcast_id = broadcasts.id AND status = \'pending\')
        ');

        return $stats;
    }

    protected function dueTargets()
    {
        $now = $this->now();
        $stmt = $this->store->pdo->prepare('
            SELECT t.*, b.method, b.from_chat_id, b.message_id, b.text, b.parse_mode
            FROM targets t
            JOIN broadcasts b ON b.id = t.broadcast_id
            LEFT JOIN chat_state s ON s.chat_id = t.chat_id
            WHERE t.status = \'pending\'
              AND b.status = \'running\'
              AND t.next_attempt_at <= :now
              AND (t.chat_id > 0 OR s.last_sent_at IS NULL OR s.last_sent_at <= :gate)
            ORDER BY t.id
            LIMIT :batch
        ');
        $stmt->bindValue(':now', $now);
        $stmt->bindValue(':gate', $now - $this->opts['group_interval']);
        $stmt->bindValue(':batch', (int)$this->opts['batch'], PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    protected function deliver(array $t)
    {
        try {
            if ($t['method'] === 'copy') {
                $res = $this->telegram->botJson('copyMessage', [
                    'chat_id' => (int)$t['chat_id'],
                    'from_chat_id' => (int)$t['from_chat_id'],
                    'message_id' => (int)$t['message_id'],
                ]);
            } else {
                $res = $this->telegram->botJson('sendMessage', [
                    'chat_id' => (int)$t['chat_id'],
                    'text' => $t['text'],
                    'parse_mode' => $t['parse_mode'],
                    'disable_web_page_preview' => true,
                ]);
            }
        } catch (\Throwable $e) {
            return $this->retryLater($t, $e->getMessage());
        }

        if (!empty($res['ok'])) {
            $this->store->pdo->prepare('UPDATE targets SET status = \'sent\', sent_at = ?, attempts = attempts + 1 WHERE id = ?')
                ->execute([time(), $t['id']]);
            $this->store->pdo->prepare('INSERT INTO chat_state (chat_id, last_sent_at) VALUES (?, ?) ON CONFLICT(chat_id) DO UPDATE SET last_sent_at = excluded.last_sent_at')
                ->execute([$t['chat_id'], $this->now()]);
            return 'sent';
        }

        $code = (int)($res['error_code'] ?? 0);
        $desc = (string)($res['description'] ?? 'unknown error');

        if ($code === 429) {
            $wait = (int)($res['parameters']['retry_after'] ?? 5);
            $this->store->setMeta('paused_until', $this->now() + $wait + 1);
            return 'paused';
        }

        if (isset($res['parameters']['migrate_to_chat_id'])) {
            return $this->migrate($t, (int)$res['parameters']['migrate_to_chat_id']);
        }

        if ($code === 400 && preg_match('/message to copy not found|message can\'t be copied|message text is empty|can\'t parse entities/i', $desc)) {
            $this->store->pdo->prepare('UPDATE broadcasts SET status = \'aborted\' WHERE id = ?')->execute([$t['broadcast_id']]);
            error_log("Broadcast {$t['broadcast_id']} to'xtatildi: $desc");
            return 'failed';
        }

        if ($code === 403 || ($code === 400 && preg_match('/chat not found|bot was kicked|bot is not a member|user is deactivated|have no rights to send|PEER_ID_INVALID|CHAT_WRITE_FORBIDDEN/i', $desc))) {
            $this->finish($t, 'blocked', $desc);
            $this->store->pdo->prepare('UPDATE chats SET active = 0 WHERE chat_id = ?')->execute([$t['chat_id']]);
            return 'blocked';
        }

        if ($code >= 500 || $code === 0) {
            return $this->retryLater($t, $desc);
        }

        $this->finish($t, 'failed', $desc);
        return 'failed';
    }

    protected function finish(array $t, $status, $error)
    {
        $this->store->pdo->prepare('UPDATE targets SET status = ?, last_error = ?, attempts = attempts + 1 WHERE id = ?')
            ->execute([$status, mb_substr($error, 0, 250), $t['id']]);
    }

    protected function retryLater(array $t, $error)
    {
        $attempts = (int)$t['attempts'] + 1;
        if ($attempts >= $this->opts['max_attempts']) {
            $this->finish($t, 'failed', $error);
            return 'failed';
        }

        $this->store->pdo->prepare('UPDATE targets SET attempts = ?, last_error = ?, next_attempt_at = ? WHERE id = ?')
            ->execute([$attempts, mb_substr($error, 0, 250), $this->now() + min(300, 5 * (2 ** $attempts)), $t['id']]);
        return 'retry';
    }

    protected function migrate(array $t, $newChatId)
    {
        $pdo = $this->store->pdo;
        $pdo->prepare('UPDATE chats SET active = 0 WHERE chat_id = ?')->execute([$t['chat_id']]);
        $pdo->prepare('INSERT OR IGNORE INTO chats (chat_id, type, first_seen) VALUES (?, \'supergroup\', ?)')->execute([$newChatId, time()]);

        $exists = $pdo->prepare('SELECT 1 FROM targets WHERE broadcast_id = ? AND chat_id = ?');
        $exists->execute([$t['broadcast_id'], $newChatId]);
        if ($exists->fetchColumn()) {
            $this->finish($t, 'failed', "migrated to $newChatId");
            return 'failed';
        }

        $pdo->prepare('UPDATE targets SET chat_id = ?, next_attempt_at = 0 WHERE id = ?')->execute([$newChatId, $t['id']]);
        return 'retry';
    }

    public function status()
    {
        return $this->store->pdo->query('
            SELECT b.id, b.method, b.status, b.created_at,
                   SUM(t.status = \'pending\') AS pending,
                   SUM(t.status = \'sent\') AS sent,
                   SUM(t.status = \'blocked\') AS blocked,
                   SUM(t.status = \'failed\') AS failed
            FROM broadcasts b LEFT JOIN targets t ON t.broadcast_id = b.id
            GROUP BY b.id ORDER BY b.id DESC LIMIT 20
        ')->fetchAll();
    }

    public function cancel($id)
    {
        $stmt = $this->store->pdo->prepare('UPDATE broadcasts SET status = \'cancelled\' WHERE id = ? AND status = \'running\'');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}

function broadcastCli(array $argv)
{
    $config = is_file(__DIR__ . '/config.php') ? require __DIR__ . '/config.php' : [];
    $token = getenv('BOT_TOKEN') ?: ($config['bot_token'] ?? '');
    $dbPath = $config['db_path'] ?? __DIR__ . '/data/bot.sqlite';

    if ($token === '') {
        fwrite(STDERR, "BOT_TOKEN sozlanmagan\n");
        return 1;
    }

    $command = $argv[1] ?? '';
    $args = [];
    foreach (array_slice($argv, 2) as $a) {
        if (preg_match('/^--([\w-]+)(?:=(.*))?$/s', $a, $m)) {
            $args[$m[1]] = $m[2] ?? true;
        } else {
            $args[] = $a;
        }
    }

    $store = new Storage($dbPath);
    $opts = array_filter([
        'rate' => $config['broadcast_rate'] ?? null,
        'group_interval' => $config['broadcast_group_interval'] ?? null,
        'max_runtime' => $config['broadcast_max_runtime'] ?? null,
    ], static fn($v) => $v !== null);
    $broadcaster = new Broadcaster($store, new Telegram($token), $opts);

    switch ($command) {
        case 'run':
            $lock = fopen($dbPath . '.lock', 'c');
            if (!flock($lock, LOCK_EX | LOCK_NB)) {
                return 0;
            }
            $stats = $broadcaster->run();
            echo json_encode($stats), "\n";
            return 0;

        case 'add':
            if (isset($args['text'])) {
                $spec = ['method' => 'text', 'text' => $args['text'], 'parse_mode' => $args['parse-mode'] ?? null];
            } elseif (isset($args['from'], $args['message'])) {
                $spec = ['method' => 'copy', 'from_chat_id' => (int)$args['from'], 'message_id' => (int)$args['message']];
            } else {
                fwrite(STDERR, "Foydalanish: add --text=\"...\" [--parse-mode=HTML] | add --from=CHAT_ID --message=ID  [--only=groups|private] [--file=ids.txt]\n");
                return 1;
            }

            if (isset($args['file'])) {
                $lines = is_file($args['file']) ? file($args['file'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
                $chatIds = array_map('intval', array_filter(array_map('trim', $lines), 'is_numeric'));
            } else {
                $where = 'active = 1';
                if (($args['only'] ?? '') === 'groups') {
                    $where .= ' AND chat_id < 0';
                } elseif (($args['only'] ?? '') === 'private') {
                    $where .= ' AND chat_id > 0';
                }
                $chatIds = array_map('intval', $store->pdo->query("SELECT chat_id FROM chats WHERE $where")->fetchAll(PDO::FETCH_COLUMN));
            }

            if (!$chatIds) {
                fwrite(STDERR, "Qabul qiluvchilar topilmadi\n");
                return 1;
            }
            $id = $broadcaster->enqueue($spec, $chatIds);
            echo "Broadcast #$id: " . count($chatIds) . " ta chat navbatga qo'yildi\n";
            return 0;

        case 'status':
            foreach ($broadcaster->status() as $row) {
                echo implode("\t", [
                    "#{$row['id']}", $row['status'], $row['method'], date('Y-m-d H:i', $row['created_at']),
                    "pending={$row['pending']}", "sent={$row['sent']}", "blocked={$row['blocked']}", "failed={$row['failed']}",
                ]), "\n";
            }
            return 0;

        case 'cancel':
            echo $broadcaster->cancel((int)($args[0] ?? 0)) ? "Bekor qilindi\n" : "Topilmadi yoki tugagan\n";
            return 0;
    }

    fwrite(STDERR, "Buyruqlar: add | run | status | cancel ID\n");
    return 1;
}

if (PHP_SAPI === 'cli' && isset($argv[0]) && realpath($argv[0]) === __FILE__) {
    exit(broadcastCli($argv));
}
