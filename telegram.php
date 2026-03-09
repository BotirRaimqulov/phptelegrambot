<?php
class Telegram
{
    protected $api_key;

    public function __construct($api_key)
    {
        $this->api_key = $api_key;
    }

    public function bot($method, $datas = [])
    {
        $url = "https://api.telegram.org/bot" . $this->api_key . "/" . $method;
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $datas
        ));

        $multi_curl = curl_multi_init();
        curl_multi_add_handle($multi_curl, $curl);

        $active = null;
        do {
            $status = curl_multi_exec($multi_curl, $active);
            if ($active) {
                curl_multi_select($multi_curl);
            }
        } while ($status === CURLM_CALL_MULTI_PERFORM || $active);

        $res = curl_multi_getcontent($curl);

        curl_multi_remove_handle($multi_curl, $curl);
        curl_multi_close($multi_curl);

        if (curl_error($curl)) {
            throw new \Exception(curl_error($curl));
        }

        return json_decode($res, true);
    }

    public function botJson($method, $datas = [])
    {
        $url = "https://api.telegram.org/bot" . $this->api_key . "/" . $method;
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($datas)
        ));

        $multi_curl = curl_multi_init();
        curl_multi_add_handle($multi_curl, $curl);

        $active = null;
        do {
            $status = curl_multi_exec($multi_curl, $active);
            if ($active) {
                curl_multi_select($multi_curl);
            }
        } while ($status === CURLM_CALL_MULTI_PERFORM || $active);

        $res = curl_multi_getcontent($curl);

        curl_multi_remove_handle($multi_curl, $curl);
        curl_multi_close($multi_curl);

        if (curl_error($curl)) {
            throw new \Exception(curl_error($curl));
        }

        return json_decode($res, true);
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
    public function setWebhook($url)
    {
        $params = [
            'url' => $url,
            'allowed_updates' => ["message", "edited_channel_post", "callback_query"]
        ];
        return $this->bot('setWebhook', $params);
    }

    public function utf16len($s)
    {
        return strlen(mb_convert_encoding($s, "UTF-16LE", "UTF-8")) / 2;
    }

    public function update()
    {
        return json_decode(file_get_contents("php://input"), true);
    }
}
