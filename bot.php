<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'telegram.php';

$api_key = 'BOT_TOKEN';
$telegram = new Telegram($api_key);
$bot_name = "BOT_USER";


$update = $telegram->update();
if ($update) {

    if (isset($update['message'])) {
        $msg = $update['message'];
        $first_name = $msg['from']['first_name'];
        $last_name = $msg['last_name'];
        $type = $msg['chat']['type'];
        $tx = $msg['text'];
        $cid = $msg['chat']['id'];
        $chatId = $update['message']['chat']['id'];
        $mid = $msg['message_id'];
    }

    if (isset($update['callback_query'])) {
        $data = $update['callback_query']['data'];
        $mid = $update['callback_query']['message']['message_id'];
        $cid = $update['callback_query']['message']['chat']['id'];
        $chatId = $update['callback_query']['message']['chat']['id'];
    }

    $telegram->sendChatAction($chatId, 'typing');
    if ($tx == "/start") {
        $telegram->sendMessage($chatId, "🔰 Assalomu alaykum!");
    }
}


