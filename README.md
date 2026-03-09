# PHP Telegram Bot

Telegram Bot API uchun oddiy PHP kutubxona. Premium emoji, tugma style lari va Bot API 9.4+ funksiyalarni qo'llab-quvvatlaydi.

## O'rnatish

1. `telegram.php` va `bot.php` ni serverga yuklang
2. `bot.php` dagi `BOT_TOKEN` ni o'z tokeningiz bilan almashtiring
3. Webhook o'rnating:
```
https://api.telegram.org/bot<TOKEN>/setWebhook?url=https://yourdomain.com/bot.php
```

## Premium Emoji

Bot API 9.4 dan boshlab botlar premium (custom) emoji ishlatishi mumkin. Shart: bot egasi Telegram Premium obunachisi bo'lishi kerak yoki bot Fragment orqali qo'shimcha username sotib olgan bo'lishi kerak.

### 1. HTML orqali
```php
$telegram->sendPremiumMessage($chatId,
    '<tg-emoji emoji-id="5368324170671202286">🔥</tg-emoji> Salom!',
    "HTML"
);
```

### 2. MarkdownV2 orqali
```php
$telegram->sendPremiumMessage($chatId,
    '![🔥](tg://emoji?id=5368324170671202286) Salom\!',
    "MarkdownV2"
);
```

### 3. Entities orqali (parse_mode kerak emas)
```php
$text = "🔥 Premium emoji";
$entities = [
    [
        "type" => "custom_emoji",
        "offset" => 0,
        "length" => $telegram->utf16len("🔥"),
        "custom_emoji_id" => "5368324170671202286",
    ],
];
$telegram->sendMessageEntities($chatId, $text, $entities);
```

## Inline tugmalarda Premium Emoji va Style

Bot API 9.4+ da tugmalarga `icon_custom_emoji_id` va `style` berish mumkin.

Style turlari:
- `"success"` — yashil
- `"danger"` — qizil
- `"primary"` — ko'k

```php
$keyboard = [
    [
        ["text" => "Ha", "callback_data" => "yes", "icon_custom_emoji_id" => "5471952986970267163", "style" => "success"],
        ["text" => "Yoq", "callback_data" => "no", "icon_custom_emoji_id" => "5382322671028990089", "style" => "danger"],
    ],
    [
        ["text" => "Batafsil", "callback_data" => "info", "style" => "primary"],
    ],
];

$telegram->sendPremiumMessage($chatId, "Tanlang:", "HTML", $keyboard);
```

## Emoji ID ni qanday topish

1. Botga premium emoji yuboring — bot ID ni qaytaradi
2. Yoki `@EmojiInfoBot` dan foydalaning

## Methodlar

| Method | Tavsif |
|---|---|
| `sendMessage()` | Oddiy xabar yuborish |
| `sendMessageEntities()` | Entities bilan xabar (premium emoji) |
| `sendPremiumMessage()` | Premium emoji + inline tugmalar |
| `editPremiumMessage()` | Premium xabarni tahrirlash |
| `answerCallbackQuery()` | Callback javob |
| `utf16len()` | UTF-16 uzunlik hisoblash |
| `sendPhoto()` | Rasm yuborish |
| `sendVideo()` | Video yuborish |
| `sendAudio()` | Audio yuborish |
| `sendDocument()` | Hujjat yuborish |
| `editMessageText()` | Xabar matnini tahrirlash |
| `editMessageReplyMarkup()` | Tugmalarni tahrirlash |
| `editMessageCaption()` | Caption tahrirlash |
| `copyMessage()` | Xabarni nusxalash |
| `deleteMessage()` | Xabarni o'chirish |
| `sendChatAction()` | Chat action yuborish |
| `setWebhook()` | Webhook o'rnatish |

## Bot commandlari

| Command | Tavsif |
|---|---|
| `/start` | Boshlash |
| `/html` | HTML orqali premium emoji |
| `/markdown` | MarkdownV2 orqali premium emoji |
| `/entities` | Entities orqali premium emoji |
| `/menu` | Premium emoji + rangli tugmalar |

Premium emoji yuborilsa — avtomatik ID ni qaytaradi.
