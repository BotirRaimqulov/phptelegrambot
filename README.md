PHP Telegram Bot

PHP yordamida Telegram botlarini yaratish uchun mo'ljallangan. Ushbu repository Telegram Bot API bilan ishlashni osonlashtiruvchi klasslarni va funksiyalarni o'z ichiga oladi.

Boshlang'ich Talablar

PHP 7.4 yoki undan yuqori versiya.

cURL PHP kengaytmasi faollashtirilgan bo'lishi kerak.

Telegram botining API tokeni (@BotFather orqali olinadi).

O'rnatish

Repositoryni yuklab oling yoki klon qiling:

git clone https://github.com/BotirRaimqulov/phptelegrambot.git
cd phptelegrambot

Bot tokeningizni bot.php fayliga joylashtiring:

$api_key = 'YOUR_BOT_TOKEN';

Foydalanish

bot.php faylini ishga tushirish

bot.php fayli botingizni boshqaradi va foydalanuvchilarning xabarlariga javob beradi. Faylni ishga tushirishdan oldin, telegram.php klassi orqali webhook sozlanishi kerak.

Webhookni sozlash

Webhookni sozlash uchun quyidagi koddan foydalaning:

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

Webhook muvaffaqiyatli o'rnatilgandan so'ng, bot.php fayli foydalanuvchi xabarlarini qayta ishlash uchun ishlatiladi.

Xabarlarni qayta ishlash

bot.php fayli foydalanuvchi yuborgan xabarlarni qayta ishlaydi va ularga javob beradi. Misol:

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

Asosiy Funksiyalar

telegram.php klassi Telegram Bot API bilan ishlash uchun bir qator qulay funksiyalarni taqdim etadi:

sendMessage: Foydalanuvchiga matnli xabar yuboradi.

$telegram->sendMessage($chat_id, "Xabar matni", "markdown");

sendPhoto: Foydalanuvchiga rasm yuboradi.

$telegram->sendPhoto($chat_id, "https://example.com/image.jpg", "Rasm tavsifi");

sendDocument: Hujjat yuboradi.

$telegram->sendDocument($chat_id, "https://example.com/file.pdf", "Hujjat tavsifi");

deleteMessage: Xabarni o'chiradi.

$telegram->deleteMessage($chat_id, $message_id);

editMessageText: Yuborilgan xabar matnini tahrirlaydi.

$telegram->editMessageText($chat_id, $message_id, "Yangi matn");

Hissa Qo'shish

Agar loyihani yaxshilash yoki yangi funksiyalar qo'shmoqchi bo'lsangiz, pull request yuborishingiz mumkin. Xatoliklarni yoki takliflarni Issues bo'limida bildiring.
