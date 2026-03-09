<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'telegram.php';

$api_key = 'BOT_TOKEN';
$telegram = new Telegram($api_key);
$bot_name = "BOT_USER";

$FIRE = "5368324170671202286";
$STAR = "5471952986970267163";
$ROCKET = "5386367538735104399";
$CHECK = "5382322671028990089";

$update = $telegram->update();
if ($update) {

    if (isset($update['message'])) {
        $msg = $update['message'];
        $first_name = $msg['from']['first_name'];
        $last_name = $msg['last_name'] ?? '';
        $type = $msg['chat']['type'];
        $tx = $msg['text'] ?? '';
        $cid = $msg['chat']['id'];
        $chatId = $update['message']['chat']['id'];
        $mid = $msg['message_id'];
        $entities = $msg['entities'] ?? [];
    }

    if (isset($update['callback_query'])) {
        $data = $update['callback_query']['data'];
        $mid = $update['callback_query']['message']['message_id'];
        $cid = $update['callback_query']['message']['chat']['id'];
        $chatId = $update['callback_query']['message']['chat']['id'];
        $callbackId = $update['callback_query']['id'];

        $telegram->answerCallbackQuery($callbackId, "$data bosildi");
    }

    $telegram->sendChatAction($chatId, 'typing');


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


    if (isset($entities) && !empty($entities)) {
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
