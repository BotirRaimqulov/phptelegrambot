
Mana optimallashtirilgan va chiroyli README:

---

# 🌟 PHP Telegram Bot

PHP yordamida Telegram botlarini yaratish uchun oson va qulay kutubxona. Ushbu repository Telegram Bot API bilan ishlashni sezilarli darajada soddalashtiradi.

---

## 📋 Talablar

- **PHP 7.4 yoki undan yuqori**  
- `cURL` PHP kengaytmasi faol bo'lishi kerak  
- Telegram API tokeni — [BotFather](https://t.me/BotFather) orqali olinadi  

---

## 🚀 O'rnatish

1. **Repositoryni klonlash:**
   ```bash
   git clone https://github.com/BotirRaimqulov/phptelegrambot.git
   cd phptelegrambot
   ```

2. **Bot tokenini kiritish:**
   Faylda tokeningizni yangilang:
   ```php
   $api_key = 'YOUR_BOT_TOKEN';
   ```

---

## 📦 Foydalanish

### **Webhook sozlash**
Telegram botingizni to'g'ri ishlashi uchun webhook o'rnatishingiz kerak. Quyidagi koddan foydalaning:

```php
require_once 'telegram.php';

$api_key = 'YOUR_BOT_TOKEN';
$telegram = new Telegram($api_key);

$webhook_url = 'https://your-domain.com/bot.php';
$response = $telegram->setWebhook($webhook_url);

if ($response['ok']) {
    echo "Webhook muvaffaqiyatli o'rnatildi!";
} else {
    echo "Webhookni o'rnatishda xatolik yuz berdi!";
}
```

### **Foydalanuvchilarning xabarlarini qayta ishlash**
Foydalanuvchilar tomonidan yuborilgan xabarlarni boshqarish uchun `bot.php` fayli tuzilgan. Quyida oddiy misol:

```php
require_once 'telegram.php';

$api_key = 'YOUR_BOT_TOKEN';
$telegram = new Telegram($api_key);

$update = $telegram->update();
$chat_id = $update['message']['chat']['id'] ?? null;
$text = $update['message']['text'] ?? null;

if ($chat_id && $text) {
    if ($text === '/start') {
        $telegram->sendMessage($chat_id, "Salom! Botimizga xush kelibsiz.");
    } elseif ($text === '/help') {
        $telegram->sendMessage($chat_id, "Bu bot Telegram API bilan ishlash uchun yaratilgan.");
    } else {
        $telegram->sendMessage($chat_id, "Siz yuborgan: $text");
    }
}
```

---

## 🔑 Asosiy Funksiyalar

`telegram.php` klassi Telegram Bot API bilan ishlashni soddalashtiradigan quyidagi funksiyalarni o'z ichiga oladi:

| Funksiya                 | Ta'rifi                                                     |
|--------------------------|------------------------------------------------------------|
| **sendMessage**          | Matnli xabar yuboradi                                      |
| **sendPhoto**            | Foydalanuvchiga rasm yuboradi                              |
| **sendDocument**         | Hujjat yuboradi                                            |
| **deleteMessage**        | Xabarni o'chiradi                                          |
| **editMessageText**      | Yuborilgan xabarni tahrirlaydi                             |

Misollar:

- **Matnli xabar yuborish:**
  ```php
  $telegram->sendMessage($chat_id, "Xabar matni", "markdown");
  ```

- **Rasm yuborish:**
  ```php
  $telegram->sendPhoto($chat_id, "https://example.com/image.jpg", "Rasm tavsifi");
  ```

---

## 🛠 Hissa Qo'shish

- Har qanday taklif va xatoliklar haqida **Issues** bo'limida xabar bering.
- Yaxshilanishlar yoki yangi funksiyalar uchun **Pull Request** yuborishingiz mumkin.

---
