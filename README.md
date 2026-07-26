# PHP Telegram Bot

Telegram Bot API uchun oddiy PHP kutubxona. Premium emoji, tugma style lari va Bot API 10.2 funksiyalarni qo'llab-quvvatlaydi.

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

## Ephemeral (maxfiy) xabarlar — Bot API 10.2

Guruhda faqat bitta foydalanuvchiga ko'rinadigan xabar yuborish mumkin. Xabarni bot va qabul qiluvchi foydalanuvchi ko'radi, boshqalar ko'rmaydi.

```php
$res = $telegram->sendEphemeralMessage(
    $chatId,
    $userId,
    "Bu xabarni faqat siz ko'ryapsiz.",
    "HTML"
);

$ephemeralId = $res['result']['ephemeral_message_id'];
```

Callback query ga javob sifatida (tugma bosilganda) yuborish:

```php
$telegram->sendEphemeralMessage($chatId, $userId, "Faqat sizga.", "HTML", null, $callbackQueryId);
```

Ephemeral xabarni tahrirlash va o'chirish:

```php
$telegram->editEphemeralMessageText($chatId, $ephemeralId, "Yangilangan matn", "HTML");
$telegram->editEphemeralMessageReplyMarkup($chatId, $ephemeralId, $keyboard);
$telegram->deleteEphemeralMessage($chatId, $ephemeralId);
```

## Rich Messages (bloklar) — Bot API 10.2

Xabarni bloklardan qurish mumkin: paragraf, sarlavha, ro'yxat, iqtibos, kod bloki, jadval va boshqalar.

```php
$blocks = [
    ["type" => "heading", "text" => "Sarlavha"],
    ["type" => "paragraph", "text" => "Bu oddiy paragraf matni."],
    ["type" => "quotation", "text" => "Bu iqtibos bloki."],
    ["type" => "preformatted", "text" => "echo 'kod bloki';"],
];

$telegram->sendRichMessage($chatId, $blocks);
```

## Guest Mode — Bot API 10.0

Bot a'zo bo'lmagan chatlarda ham xabar olib, javob bera oladi. Guest so'roviga javob:

```php
$telegram->answerGuestQuery($guestQueryId, "Salom, mehmon!", "HTML");
```

## Chat Join Request Queries — Bot API 10.1

Guruhga qo'shilish so'roviga interaktiv javob (tugmalar bilan) yoki Web App yuborish:

```php
$telegram->answerChatJoinRequestQuery($queryId, "Savolga javob bering:", $keyboard);
$telegram->sendChatJoinRequestWebApp($queryId, "https://yourdomain.com/verify");
```

## Reaksiyalarni boshqarish — Bot API 10.0

```php
$telegram->deleteMessageReaction($chatId, $messageId, $userId);
$telegram->deleteAllMessageReactions($chatId, $messageId);
```

## Poll (so'rovnoma) — Bot API 10.0

Endi 1 tadan variant, media, faqat a'zolar uchun (`members_only`) va boshqa yangi parametrlar qo'llab-quvvatlanadi:

```php
$telegram->sendPoll($chatId, "Sevimli tilingiz?", ["PHP", "Python", "JS"], true, "regular", [
    "members_only" => true,
    "allows_multiple_answers" => false,
]);
```

## Live Photo — Bot API 10.0

```php
$telegram->sendLivePhoto($chatId, $fileId, "Jonli surat");
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
| `sendEphemeralMessage()` | Maxfiy (ephemeral) xabar yuborish |
| `editEphemeralMessageText()` | Ephemeral xabar matnini tahrirlash |
| `editEphemeralMessageReplyMarkup()` | Ephemeral xabar tugmalarini tahrirlash |
| `deleteEphemeralMessage()` | Ephemeral xabarni o'chirish |
| `sendRichMessage()` | Bloklardan iborat rich xabar yuborish |
| `sendRichMessageDraft()` | Rich xabar qoralamasini yuborish |
| `answerChatJoinRequestQuery()` | Qo'shilish so'roviga interaktiv javob |
| `sendChatJoinRequestWebApp()` | Qo'shilish so'roviga Web App yuborish |
| `answerGuestQuery()` | Guest so'roviga javob (guest mode) |
| `deleteMessageReaction()` | Foydalanuvchi reaksiyasini o'chirish |
| `deleteAllMessageReactions()` | Barcha reaksiyalarni o'chirish |
| `getChatAdministrators()` | Chat adminlarini olish |
| `getUserPersonalChatMessages()` | Foydalanuvchi shaxsiy chat xabarlari |
| `sendLivePhoto()` | Live Photo yuborish |
| `sendPoll()` | So'rovnoma yuborish (media, members_only) |
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
| `/rich` | Bloklardan iborat rich message |
| `/secret` | Maxfiy (ephemeral) xabar |
| `/poll` | So'rovnoma (members_only) |
| `/admins` | Chat adminlari ro'yxati |

Premium emoji yuborilsa — avtomatik ID ni qaytaradi.

Guruhda guest so'rov (`guest_message`) yoki qo'shilish so'rovi (`chat_join_request`) kelganda bot avtomatik javob beradi.
