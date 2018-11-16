<?php

declare(strict_types=1);

namespace TelegramBotGanttProject\Service;

use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;

class MessageSenderService
{
    /**
     * Message data
     *
     * @var array
     */
    protected $data;

    public function __construct()
    {
    }

    /**
     * Prepares the message data to be sent. A Longman\TelegramBot\Telegram
     * object must have been previously created for this function to work.
     *
     * @param   int       $chat_id                  The chat id to send the message
     * @param   string    $text                     The text to be sent
     * @param   string    $parseMode                Parse mode for advanced formatting.
     *                                              Telegram's API supports 'HTML' or
     *                                              'Markdown' options
     * @param   boolean   $selectiveReply           Enable or disable selective reply
     * @return  void
     */
    public function prepareMessage(int $chat_id, string $text, string $parseMode = null, bool $selectiveReply = null): void
    {
        $data['chat_id']    = $chat_id;
        $data['text']       = $text;

        if (
            !\is_null($parseMode) &&
            (
                $parseMode === 'HTML' ||
                $parseMode === 'Markdown'
            )
        ) {
            $data['parse_mode'] = $parseMode;
        }

        if (!\is_null($selectiveReply)) {
            if($selectiveReply) {
                $data['reply_markup'] = Keyboard::forceReply(['selective' => true]);
            } else {
                $data['reply_markup'] = Keyboard::remove(['selective' => true]);
            }
        }

        $this->data = $data;
    }

    /**
     * Send the prepared message to Telegram
     *
     * @return ServerResponse
     */
    public function sendMessage(): ServerResponse
    {
        return Request::sendMessage($this->data);
    }

    /**
     * Returns the stored data
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }
}
