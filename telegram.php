<?php
class Telegram
{
    protected $api_key;

    public function __construct($api_key)
    {
        $this->api_key = $api_key;
    }

    /**
     * Bitta HTTP qatlami. Fayl (CURLFile) bo'lmasa JSON, bo'lsa multipart yuboradi.
     * null qiymatlar tashlab yuboriladi; ichma-ich massivlar JSON'ga o'tkaziladi.
     */
    protected function request($method, $datas = [], $forceJson = false, $retry = true)
    {
        $datas = array_filter((array)$datas, static fn($v) => $v !== null);

        $hasFile = false;
        foreach ($datas as $v) {
            if ($v instanceof \CURLFile) {
                $hasFile = true;
                break;
            }
        }

        $opts = [
            CURLOPT_URL => "https://api.telegram.org/bot" . $this->api_key . "/" . $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => $hasFile ? 120 : 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_POST => true,
        ];

        if ($hasFile && !$forceJson) {
            foreach ($datas as $k => $v) {
                if (is_array($v)) {
                    $datas[$k] = json_encode($v);
                } elseif (is_bool($v)) {
                    $datas[$k] = $v ? 'true' : 'false';
                }
            }
            $opts[CURLOPT_POSTFIELDS] = $datas;
        } else {
            $opts[CURLOPT_HTTPHEADER] = ['Content-Type: application/json'];
            $opts[CURLOPT_POSTFIELDS] = json_encode($datas);
        }

        $curl = curl_init();
        curl_setopt_array($curl, $opts);
        $res = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($res === false) {
            throw new \Exception("Telegram so'rovi muvaffaqiyatsiz ($method): $err");
        }

        $decoded = json_decode($res, true);
        if (!is_array($decoded)) {
            throw new \Exception("Telegram noto'g'ri javob qaytardi ($method)");
        }

        if (empty($decoded['ok'])) {
            $wait = $decoded['parameters']['retry_after'] ?? null;
            if ($retry && ($decoded['error_code'] ?? 0) == 429 && $wait !== null && $wait <= 5) {
                sleep((int)$wait);
                return $this->request($method, $datas, $forceJson, false);
            }
            error_log("Telegram API xatosi ($method): " . ($decoded['error_code'] ?? '?') . ' ' . ($decoded['description'] ?? ''));
        }

        return $decoded;
    }

    public function bot($method, $datas = [])
    {
        return $this->request($method, $datas);
    }

    public function botJson($method, $datas = [])
    {
        return $this->request($method, $datas, true);
    }

    public function sendMessage($chatId, $text, $parseMode = null, $replyMarkup = null)
    {
        $params = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => $parseMode,
            'reply_markup' => $replyMarkup,
            'disable_web_page_preview' => true,
        ];

        return $this->bot('sendMessage', $params);
    }

    public function sendMessageEntities($chatId, $text, $entities = [], $replyMarkup = null)
    {
        $params = [
            'chat_id' => $chatId,
            'text' => $text,
        ];
        if (!empty($entities)) {
            $params['entities'] = $entities;
        }
        if ($replyMarkup) {
            $params['reply_markup'] = $replyMarkup;
        }
        return $this->botJson('sendMessage', $params);
    }

    //
    //   $keyboard = [
    //       [
    //           ["text" => "Ha", "callback_data" => "yes", "icon_custom_emoji_id" => "...", "style" => "success"],
    //           ["text" => "Yoq", "callback_data" => "no", "style" => "danger"],
    //       ],
    //   ];
    //
    public function sendPremiumMessage($chatId, $text, $parseMode = null, $keyboard = null, $entities = null)
    {
        $params = [
            'chat_id' => $chatId,
            'text' => $text,
        ];
        if ($parseMode) {
            $params['parse_mode'] = $parseMode;
        }
        if ($entities) {
            $params['entities'] = $entities;
        }
        if ($keyboard) {
            $params['reply_markup'] = ['inline_keyboard' => $keyboard];
        }
        return $this->botJson('sendMessage', $params);
    }

    public function editPremiumMessage($chatId, $messageId, $text, $parseMode = null, $keyboard = null, $entities = null)
    {
        $params = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
        ];
        if ($parseMode) {
            $params['parse_mode'] = $parseMode;
        }
        if ($entities) {
            $params['entities'] = $entities;
        }
        if ($keyboard) {
            $params['reply_markup'] = ['inline_keyboard' => $keyboard];
        }
        return $this->botJson('editMessageText', $params);
    }

    /**
     * Ephemeral xabar. Bot API 10.3 da receiver_user_id / callback_query_id o'rniga
     * ephemeral_message_parameters ishlatiladi. $ephemeralMessageParameters berilsa,
     * u o'zgartirilmagan holda yuboriladi (maydonlari rasmiy hujjatga muvofiq bo'lishi kerak);
     * aks holda eski (10.2) maydonlar yuboriladi.
     */
    public function sendEphemeralMessage($chatId, $receiverUserId, $text, $parseMode = null, $keyboard = null, $callbackQueryId = null, $ephemeralMessageParameters = null)
    {
        $params = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => $parseMode,
        ];
        if ($ephemeralMessageParameters !== null) {
            $params['ephemeral_message_parameters'] = $ephemeralMessageParameters;
        } else {
            $params['receiver_user_id'] = $receiverUserId;
            $params['callback_query_id'] = $callbackQueryId;
        }
        if ($keyboard) {
            $params['reply_markup'] = ['inline_keyboard' => $keyboard];
        }
        return $this->botJson('sendMessage', $params);
    }

    public function editEphemeralMessageText($chatId, $ephemeralMessageId, $text, $parseMode = null, $keyboard = null)
    {
        $params = [
            'chat_id' => $chatId,
            'ephemeral_message_id' => $ephemeralMessageId,
            'text' => $text,
        ];
        if ($parseMode) {
            $params['parse_mode'] = $parseMode;
        }
        if ($keyboard) {
            $params['reply_markup'] = ['inline_keyboard' => $keyboard];
        }
        return $this->botJson('editEphemeralMessageText', $params);
    }

    public function editEphemeralMessageReplyMarkup($chatId, $ephemeralMessageId, $keyboard = null)
    {
        $params = [
            'chat_id' => $chatId,
            'ephemeral_message_id' => $ephemeralMessageId,
        ];
        if ($keyboard) {
            $params['reply_markup'] = ['inline_keyboard' => $keyboard];
        }
        return $this->botJson('editEphemeralMessageReplyMarkup', $params);
    }

    public function deleteEphemeralMessage($chatId, $ephemeralMessageId)
    {
        $params = [
            'chat_id' => $chatId,
            'ephemeral_message_id' => $ephemeralMessageId,
        ];
        return $this->botJson('deleteEphemeralMessage', $params);
    }

    public function sendRichMessage($chatId, $blocks, $keyboard = null)
    {
        $params = [
            'chat_id' => $chatId,
            'rich_message' => ['blocks' => $blocks],
        ];
        if ($keyboard) {
            $params['reply_markup'] = ['inline_keyboard' => $keyboard];
        }
        return $this->botJson('sendRichMessage', $params);
    }

    public function sendRichMessageDraft($chatId, $blocks)
    {
        $params = [
            'chat_id' => $chatId,
            'rich_message' => ['blocks' => $blocks],
        ];
        return $this->botJson('sendRichMessageDraft', $params);
    }

    public function answerChatJoinRequestQuery($queryId, $text = null, $keyboard = null)
    {
        $params = [
            'query_id' => $queryId,
        ];
        if ($text) {
            $params['text'] = $text;
        }
        if ($keyboard) {
            $params['reply_markup'] = ['inline_keyboard' => $keyboard];
        }
        return $this->botJson('answerChatJoinRequestQuery', $params);
    }

    public function sendChatJoinRequestWebApp($queryId, $webAppUrl)
    {
        $params = [
            'query_id' => $queryId,
            'web_app' => ['url' => $webAppUrl],
        ];
        return $this->botJson('sendChatJoinRequestWebApp', $params);
    }

    public function answerGuestQuery($guestQueryId, $text, $parseMode = null, $keyboard = null)
    {
        $params = [
            'guest_query_id' => $guestQueryId,
            'text' => $text,
        ];
        if ($parseMode) {
            $params['parse_mode'] = $parseMode;
        }
        if ($keyboard) {
            $params['reply_markup'] = ['inline_keyboard' => $keyboard];
        }
        return $this->botJson('answerGuestQuery', $params);
    }

    public function deleteMessageReaction($chatId, $messageId, $userId)
    {
        $params = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'user_id' => $userId,
        ];
        return $this->botJson('deleteMessageReaction', $params);
    }

    public function deleteAllMessageReactions($chatId, $messageId)
    {
        $params = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
        ];
        return $this->botJson('deleteAllMessageReactions', $params);
    }

    public function getChatAdministrators($chatId, $returnBots = false)
    {
        $params = [
            'chat_id' => $chatId,
            'return_bots' => $returnBots,
        ];
        return $this->botJson('getChatAdministrators', $params);
    }

    public function getUserPersonalChatMessages($userId, $offsetMessageId = null, $limit = null)
    {
        $params = [
            'user_id' => $userId,
        ];
        if ($offsetMessageId !== null) {
            $params['offset_message_id'] = $offsetMessageId;
        }
        if ($limit !== null) {
            $params['limit'] = $limit;
        }
        return $this->botJson('getUserPersonalChatMessages', $params);
    }

    public function sendLivePhoto($chatId, $livePhoto, $caption = null, $parseMode = null, $replyMarkup = null)
    {
        $params = [
            'chat_id' => $chatId,
            'live_photo' => $livePhoto,
            'caption' => $caption,
            'parse_mode' => $parseMode,
            'reply_markup' => $replyMarkup,
        ];
        return $this->bot('sendLivePhoto', $params);
    }

    public function sendPoll($chatId, $question, $options, $isAnonymous = true, $type = 'regular', $extra = [])
    {
        $params = [
            'chat_id' => $chatId,
            'question' => $question,
            'options' => $options,
            'is_anonymous' => $isAnonymous,
            'type' => $type,
        ];
        foreach ($extra as $key => $value) {
            $params[$key] = $value;
        }
        return $this->botJson('sendPoll', $params);
    }

    public function answerCallbackQuery($callbackQueryId, $text = null, $showAlert = false)
    {
        $params = [
            'callback_query_id' => $callbackQueryId,
            'show_alert' => $showAlert,
        ];
        if ($text) {
            $params['text'] = $text;
        }
        return $this->bot('answerCallbackQuery', $params);
    }

    public function editMessageText($chatId, $message_id, $text, $parseMode = null, $replyMarkup = null)
    {
        $params = [
            'chat_id' => $chatId,
            'message_id' => $message_id,
            'text' => $text,
            'parse_mode' => $parseMode,
            'reply_markup' => $replyMarkup,
        ];
        return $this->bot('editMessageText', $params);
    }

    public function editMessageReplyMarkup($chatId, $message_id, $replyMarkup = null)
    {
        $params = [
            'chat_id' => $chatId,
            'message_id' => $message_id,
            'reply_markup' => $replyMarkup,
        ];
        return $this->bot('editMessageReplyMarkup', $params);
    }

    public function editMessageCaption($chatId, $message_id, $caption = null, $parseMode = null, $replyMarkup = null)
    {
        $params = [
            'chat_id' => $chatId,
            'message_id' => $message_id,
            'caption' => $caption,
            'parse_mode' => $parseMode,
            'reply_markup' => $replyMarkup,
        ];
        return $this->bot('editMessageCaption', $params);
    }

    public function sendAudio($chatId, $audio, $caption = null, $parseMode = null, $replyMarkup = null)
    {
        $params = [
            'chat_id' => $chatId,
            'audio' => $audio,
            'caption' => $caption,
            'parse_mode' => $parseMode,
            'reply_markup' => $replyMarkup,
        ];

        return $this->bot('sendAudio', $params);
    }

    public function sendPhoto($chatId, $photo, $caption = null, $parseMode = null, $replyMarkup = null)
    {
        $params = [
            'chat_id' => $chatId,
            'photo' => $photo,
            'caption' => $caption,
            'parse_mode' => $parseMode,
            'reply_markup' => $replyMarkup,
        ];

        return $this->bot('sendPhoto', $params);
    }

    public function sendVideo($chatId, $video, $caption = null, $parseMode = null, $replyMarkup = null)
    {
        $params = [
            'chat_id' => $chatId,
            'video' => $video,
            'caption' => $caption,
            'parse_mode' => $parseMode,
            'reply_markup' => $replyMarkup,
        ];

        return $this->bot('sendVideo', $params);
    }

    public function sendDocument($chatId, $document, $caption = null, $parseMode = null, $replyMarkup = null)
    {
        $params = [
            'chat_id' => $chatId,
            'document' => $document,
            'caption' => $caption,
            'parse_mode' => $parseMode,
            'reply_markup' => $replyMarkup,
        ];

        return $this->bot('sendDocument', $params);
    }
    public function copyMessage($from_id, $user_id, $message_id, $parseMode = "markdown")
    {
        $params = [
            'chat_id' => $user_id,
            'from_chat_id' => $from_id,
            'message_id' => $message_id,
            'parse_mode' => $parseMode
        ];
        return $this->bot('copyMessage', $params);
    }
    public function sendChatAction($chatId, $action)
    {
        $params = [
            'chat_id' => $chatId,
            'action' => $action,
        ];

        return $this->bot('sendChatAction', $params);
    }
    public function deleteMessage($chatId, $message_id)
    {
        $params = [
            'chat_id' => $chatId,
            'message_id' => $message_id,
        ];
        return $this->bot('deleteMessage', $params);
    }
    public function setWebhook($url, $secretToken = null)
    {
        $params = [
            'url' => $url,
            'secret_token' => $secretToken,
            'allowed_updates' => ["message", "edited_channel_post", "callback_query", "bot_subscription_updated", "guest_message", "chat_join_request"]
        ];
        return $this->bot('setWebhook', $params);
    }

    public function getMe()
    {
        return $this->bot('getMe');
    }

    public function setMyCommands($commands)
    {
        return $this->bot('setMyCommands', ['commands' => $commands]);
    }

    public function utf16len($s)
    {
        return strlen(mb_convert_encoding($s, "UTF-16LE", "UTF-8")) / 2;
    }

    /**
     * Webhook so'rovining haqiqiyligini tekshiradi (X-Telegram-Bot-Api-Secret-Token).
     */
    public function verifyWebhookSecret($secretToken)
    {
        $header = $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '';
        return $secretToken !== '' && hash_equals((string)$secretToken, (string)$header);
    }

    public function update()
    {
        return json_decode(file_get_contents("php://input"), true);
    }
}
