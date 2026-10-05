<?php

class Storage
{
    public $pdo;

    public function __construct($path, $busyTimeoutMs = 5000)
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
            file_put_contents($dir . '/.htaccess', "Require all denied\n");
        }

        $this->pdo = new PDO('sqlite:' . $path, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $this->pdo->exec('PRAGMA busy_timeout = ' . (int)$busyTimeoutMs);
        $this->pdo->exec('PRAGMA journal_mode = WAL');
        $this->migrate();
    }

    protected function migrate()
    {
        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS chats (
                chat_id INTEGER PRIMARY KEY,
                type TEXT NOT NULL,
                title TEXT,
                first_seen INTEGER NOT NULL,
                active INTEGER NOT NULL DEFAULT 1
            );
            CREATE TABLE IF NOT EXISTS broadcasts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                method TEXT NOT NULL,
                from_chat_id INTEGER,
                message_id INTEGER,
                text TEXT,
                parse_mode TEXT,
                status TEXT NOT NULL DEFAULT \'running\',
                created_at INTEGER NOT NULL
            );
            CREATE TABLE IF NOT EXISTS targets (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                broadcast_id INTEGER NOT NULL,
                chat_id INTEGER NOT NULL,
                status TEXT NOT NULL DEFAULT \'pending\',
                attempts INTEGER NOT NULL DEFAULT 0,
                next_attempt_at REAL NOT NULL DEFAULT 0,
                last_error TEXT,
                sent_at INTEGER
            );
            CREATE INDEX IF NOT EXISTS idx_targets_due ON targets (status, next_attempt_at, id);
            CREATE UNIQUE INDEX IF NOT EXISTS idx_targets_unique ON targets (broadcast_id, chat_id);
            CREATE TABLE IF NOT EXISTS chat_state (
                chat_id INTEGER PRIMARY KEY,
                last_sent_at REAL NOT NULL
            );
            CREATE TABLE IF NOT EXISTS meta (
                k TEXT PRIMARY KEY,
                v TEXT NOT NULL
            );
        ');
    }

    public function touchChat($chatId, $type, $title = null)
    {
        $stmt = $this->pdo->prepare('SELECT active FROM chats WHERE chat_id = ?');
        $stmt->execute([$chatId]);
        $row = $stmt->fetch();

        if ($row === false) {
            $this->pdo->prepare('INSERT OR IGNORE INTO chats (chat_id, type, title, first_seen) VALUES (?, ?, ?, ?)')
                ->execute([$chatId, $type, $title, time()]);
        } elseif (!$row['active']) {
            $this->pdo->prepare('UPDATE chats SET active = 1 WHERE chat_id = ?')->execute([$chatId]);
        }
    }

    public function getMeta($key, $default = null)
    {
        $stmt = $this->pdo->prepare('SELECT v FROM meta WHERE k = ?');
        $stmt->execute([$key]);
        $v = $stmt->fetchColumn();
        return $v === false ? $default : $v;
    }

    public function setMeta($key, $value)
    {
        $this->pdo->prepare('INSERT INTO meta (k, v) VALUES (?, ?) ON CONFLICT(k) DO UPDATE SET v = excluded.v')
            ->execute([$key, (string)$value]);
    }
}
