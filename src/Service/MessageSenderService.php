<?php

declare(strict_types=1);

namespace MikelAlejoBR\TelegramBotGanttProject\Service;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;

class MessageSenderService
{
    public function __construct()
    {
    }

    /**
     * Sends a text message to the user. A Longman\TelegramBot\Telegram object
     * must have been previously created for this function to work.
     *
     * @param   int       $chat_id                  The chat id to send the message
     * @param   string    $text                     The text to be sent
     * @param   string    $parseMode                Parse mode for advanced formatting.
     *                                              Telegram's API supports 'HTML' or
     *                                              'Markdown' options
     * @param   boolean   $removeSelectiveReply     Remove selective reply keyboard
     * @return  ServerResponse                      A ServerResponse object
     */
    public function sendSimpleMessage(int $chat_id, string $text, string $parseMode = null, bool $removeSelectiveReply = true): ServerResponse
    {
        $data['chat_id']        = $chat_id;
        $data['text']           = $text;
        $data['reply_markup']   = Keyboard::remove(['selective' => $removeSelectiveReply]);
        if (
            !\is_null($parseMode) &&
            (
                $parseMode === 'HTML' ||
                $parseMode === 'Markdown'
            )
        ) {
            $data['parse_mode'] = $parseMode;
        }

        return Request::sendMessage($data);
    }
}
