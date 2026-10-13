<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/telegram.php';

$config = is_file(__DIR__ . '/config.php') ? require __DIR__ . '/config.php' : [];
$api_key = getenv('BOT_TOKEN') ?: ($config['bot_token'] ?? '');
$webhook_secret = getenv('WEBHOOK_SECRET') ?: ($config['webhook_secret'] ?? '');

if ($api_key === '') {
    http_response_code(500);
    exit;
}

$telegram = new Telegram($api_key, $config['api_base'] ?? 'https://api.telegram.org');

if ($webhook_secret !== '' && !$telegram->verifyWebhookSecret($webhook_secret)) {
    http_response_code(403);
    exit;
}

$update = $telegram->update();
$msg = $update['message'] ?? null;
if (!isset($msg['text'])) {
    exit;
}

$cid = $msg['chat']['id'];
$tx = preg_replace('/^(\/\w+)@\w+/', '$1', $msg['text']);
$button = "⚡Bot Tezligi⚡";

if ($tx === '/start') {
    header('Content-Type: application/json');
    echo json_encode([
        'method' => 'sendMessage',
        'chat_id' => $cid,
        'text' => "*XostNet.uz botlar tezligini o'lchash uchun yaratildi!*",
        'parse_mode' => 'Markdown',
        'reply_markup' => [
            'resize_keyboard' => true,
            'keyboard' => [[['text' => $button]]],
        ],
    ]);
    exit;
}

if ($tx === $button || $tx === '/speed') {
    $serverMs = (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000;

    $t0 = hrtime(true);
    $res = $telegram->botJson('sendMessage', [
        'chat_id' => $cid,
        'text' => "*Bot tezligi o'lchanmoqda...*",
        'parse_mode' => 'Markdown',
    ]);
    $apiMs = (hrtime(true) - $t0) / 1e6;

    $mid = $res['result']['message_id'] ?? null;
    if ($mid) {
        $telegram->botJson('editMessageText', [
            'chat_id' => $cid,
            'message_id' => $mid,
            'text' => sprintf("*Botimiz tezligi:* `%d ms`\n_server: %d ms_", round($apiMs), round($serverMs)),
            'parse_mode' => 'Markdown',
        ]);
    }
}
