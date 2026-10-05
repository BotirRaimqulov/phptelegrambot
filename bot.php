<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/telegram.php';
require_once __DIR__ . '/storage.php';

$config = is_file(__DIR__ . '/config.php') ? require __DIR__ . '/config.php' : [];
$api_key = getenv('BOT_TOKEN') ?: ($config['bot_token'] ?? '');
$webhook_secret = getenv('WEBHOOK_SECRET') ?: ($config['webhook_secret'] ?? '');
$admin_ids = array_filter(array_map('trim', explode(',', getenv('ADMIN_IDS') ?: ($config['admin_ids'] ?? ''))));

if ($api_key === '') {
    http_response_code(500);
    exit('BOT_TOKEN sozlanmagan');
}

$telegram = new Telegram($api_key);

if ($webhook_secret !== '' && !$telegram->verifyWebhookSecret($webhook_secret)) {
    http_response_code(403);
    exit;
}

$FIRE = "5368324170671202286";
$STAR = "5471952986970267163";
$ROCKET = "5386367538735104399";
$CHECK = "5382322671028990089";

$update = $telegram->update();
if ($update) {
    $chatId = null;
    $tx = '';
    $type = '';
    $entities = [];
    $fromId = null;

    if (isset($update['message'])) {
        $msg = $update['message'];
        $first_name = $msg['from']['first_name'] ?? '';
        $last_name = $msg['from']['last_name'] ?? '';
        $fromId = $msg['from']['id'] ?? null;
        $type = $msg['chat']['type'];
        $tx = $msg['text'] ?? '';
        $cid = $msg['chat']['id'];
        $chatId = $cid;
        $mid = $msg['message_id'];
        $entities = $msg['entities'] ?? [];

        $tx = preg_replace('/^(\/\w+)@\w+/', '$1', $tx);

        try {
            (new Storage($config['db_path'] ?? __DIR__ . '/data/bot.sqlite', 500))
                ->touchChat($cid, $type, $msg['chat']['title'] ?? null);
        } catch (\Throwable $e) {
            error_log('Chat saqlanmadi: ' . $e->getMessage());
        }
    }

    if (isset($update['callback_query'])) {
        $data = $update['callback_query']['data'];
        $mid = $update['callback_query']['message']['message_id'];
        $cid = $update['callback_query']['message']['chat']['id'];
        $chatId = $update['callback_query']['message']['chat']['id'];
        $callbackId = $update['callback_query']['id'];
        $cbUser = $update['callback_query']['from'];

        switch ($data) {
            case 'profile':
                $name = trim(($cbUser['first_name'] ?? '') . ' ' . ($cbUser['last_name'] ?? ''));
                $telegram->answerCallbackQuery($callbackId, "$name\nID: {$cbUser['id']}", true);
                break;
            case 'settings':
                $telegram->answerCallbackQuery($callbackId);
                $telegram->editMessageText($chatId, $mid, "⚙️ Sozlamalar hozircha mavjud emas.");
                break;
            case 'cancel':
                $telegram->answerCallbackQuery($callbackId, "Bekor qilindi");
                $telegram->deleteMessage($chatId, $mid);
                break;
            case 'join_ok':
                $telegram->answerCallbackQuery($callbackId, "Tasdiqlandi");
                break;
            default:
                $telegram->answerCallbackQuery($callbackId, "$data bosildi");
        }
    }

    if ($chatId !== null && $tx !== '') {
        $telegram->sendChatAction($chatId, 'typing');
    }


    if ($tx == "/start") {
        $telegram->sendMessage($chatId, "🔰 Assalomu alaykum!");
    }


    if ($tx == "/html") {
        $telegram->sendPremiumMessage($chatId,
            "<tg-emoji emoji-id=\"$STAR\">⭐</tg-emoji> Salom!\n"
            . "<tg-emoji emoji-id=\"$FIRE\">🔥</tg-emoji> Premium emoji <b>HTML</b> orqali",
            "HTML"
        );
    }


    if ($tx == "/markdown") {
        $telegram->sendPremiumMessage($chatId,
            "![⭐](tg://emoji?id=$STAR) Salom\\!\n"
            . "![🔥](tg://emoji?id=$FIRE) Premium emoji *MarkdownV2* orqali",
            "MarkdownV2"
        );
    }


    if ($tx == "/entities") {
        $p1 = "⭐";
        $p2 = " Salom! ";
        $p3 = "🔥";
        $p4 = " Premium emoji entities orqali";

        $text = $p1 . $p2 . $p3 . $p4;

        $ent = [
            [
                "type" => "custom_emoji",
                "offset" => 0,
                "length" => $telegram->utf16len($p1),
                "custom_emoji_id" => $STAR,
            ],
            [
                "type" => "custom_emoji",
                "offset" => $telegram->utf16len($p1 . $p2),
                "length" => $telegram->utf16len($p3),
                "custom_emoji_id" => $FIRE,
            ],
        ];

        $telegram->sendMessageEntities($chatId, $text, $ent);
    }


    if ($tx == "/menu") {
        $text = "<tg-emoji emoji-id=\"$STAR\">⭐</tg-emoji> <b>Asosiy menyu</b>\n\n"
              . "<tg-emoji emoji-id=\"$FIRE\">🔥</tg-emoji> Tugmani tanlang:";

        $keyboard = [
            [
                ["text" => "Profil", "callback_data" => "profile", "icon_custom_emoji_id" => $STAR],
                ["text" => "Sozlamalar", "callback_data" => "settings", "icon_custom_emoji_id" => $FIRE],
            ],
            [
                ["text" => "Premium", "callback_data" => "premium", "icon_custom_emoji_id" => $ROCKET, "style" => "success"],
            ],
            [
                ["text" => "Bekor qilish", "callback_data" => "cancel", "icon_custom_emoji_id" => $CHECK, "style" => "danger"],
            ],
        ];

        $telegram->sendPremiumMessage($chatId, $text, "HTML", $keyboard);
    }


    if ($tx == "/rich") {
        $blocks = [
            ["type" => "heading", "text" => "Bot API 10.3"],
            ["type" => "paragraph", "text" => "Bu rich message bloklardan iborat."],
            ["type" => "quotation", "text" => "Bloklar orqali chiroyli formatlash."],
            ["type" => "preformatted", "text" => "echo 'Salom, dunyo!';"],
        ];
        $telegram->sendRichMessage($chatId, $blocks);
    }


    if (strpos($tx, "/secret") === 0) {
        $args = preg_split('/\s+/', trim($tx), -1, PREG_SPLIT_NO_EMPTY);
        array_shift($args);
        $targetUserId = array_pop($args);
        $messageText = implode(" ", $args);

        if (!in_array((string)$fromId, array_map('strval', $admin_ids), true)) {
            $telegram->sendMessage($chatId, "Bu buyruq faqat bot adminlari uchun.");
        } elseif (empty($messageText) || !ctype_digit((string)$targetUserId)) {
            $telegram->sendMessage($chatId, "Foydalanish: /secret {matn} {user_id}");
        } else {
            $telegram->sendEphemeralMessage($chatId, $targetUserId, $messageText, "HTML");
        }
    }


    if ($tx == "/poll") {
        $extra = ["allows_multiple_answers" => false];
        if ($type == "channel") {
            $extra["members_only"] = true;
        }
        $telegram->sendPoll($chatId, "Sevimli tilingiz?", ["PHP", "Python", "JavaScript"], true, "regular", $extra);
    }


    if ($tx == "/admins") {
        $res = $telegram->getChatAdministrators($chatId, true);
        $names = [];
        foreach (($res['result'] ?? []) as $admin) {
            $names[] = $admin['user']['first_name'] ?? 'Admin';
        }
        $telegram->sendMessage($chatId, "Adminlar: " . implode(", ", $names));
    }


    if (isset($update['guest_message'])) {
        $guest = $update['guest_message'];
        $guestQueryId = $guest['guest_query_id'];
        $telegram->answerGuestQuery($guestQueryId,
            "<tg-emoji emoji-id=\"$ROCKET\">🚀</tg-emoji> Salom, mehmon!",
            "HTML"
        );
    }


    if (isset($update['chat_join_request'])) {
        $req = $update['chat_join_request'];
        $queryId = $req['query_id'] ?? null;
        if ($queryId) {
            $keyboard = [
                [
                    ["text" => "Tasdiqlash", "callback_data" => "join_ok", "style" => "success"],
                ],
            ];
            $telegram->answerChatJoinRequestQuery($queryId, "Guruhga qo'shilish uchun tasdiqlang:", $keyboard);
        }
    }


    if (!empty($entities) && $chatId !== null) {
        $ids = [];
        foreach ($entities as $e) {
            if ($e['type'] === 'custom_emoji' && isset($e['custom_emoji_id'])) {
                $ids[] = $e['custom_emoji_id'];
            }
        }
        if (!empty($ids)) {
            $lines = implode("\n", array_map(fn($id) => "<code>$id</code>", $ids));
            $telegram->sendPremiumMessage($chatId, "Emoji ID lar:\n$lines", "HTML");
        }
    }
}
