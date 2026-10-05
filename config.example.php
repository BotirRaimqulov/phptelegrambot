<?php
// Nusxa oling: cp config.example.php config.php (config.php git'ga kirmaydi)
return [
    'bot_token' => 'BOT_TOKEN',
    // setWebhook() ga bering; 1-256 belgi, faqat A-Z a-z 0-9 _ -
    'webhook_secret' => '',
    // /secret kabi buyruqlarga ruxsat berilgan user_id lar (vergul bilan)
    'admin_ids' => '',
    // SQLite fayl yo'li. Imkon bo'lsa veb-ildizdan tashqarida saqlang.
    'db_path' => __DIR__ . '/data/bot.sqlite',
    // Broadcast: soniyasiga xabar (umumiy limit ~30, qolganini bot javoblari uchun qoldiring)
    'broadcast_rate' => 20,
    // Bitta guruhga ketma-ket broadcast xabarlari orasidagi minimal soniya (limit 20 xabar/daqiqa)
    'broadcast_group_interval' => 6,
    // Bitta cron ishga tushishining maksimal davomiyligi (soniya), cron oralig'idan kichik bo'lsin
    'broadcast_max_runtime' => 50,
];
