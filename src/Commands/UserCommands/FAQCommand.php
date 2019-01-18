<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Longman\TelegramBot\Commands\UserCommands;

use IkastenBot\Misc\DBikastenbot;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Entities\PhotoSize;
use Longman\TelegramBot\Request;

class FAQCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'faq';

    /**
     * @var string
     */
    protected $description = 'Preguntas frecuentes sobre el TFG';

    /**
     * @var string
     */
    protected $usage = '/faq';

    /**
     * @var string
     */
    protected $version = '0.0.1';

    /**
     * @var bool
     */
    protected $need_mysql = true;

    /**
     * Conversation Object
     *
     * @var \Longman\TelegramBot\Conversation
     */
    protected $conversation;

    /**
     * Command execute method
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */






    public function execute()
    {
        $callback_query = $this->getUpdate()->getCallbackQuery();
        $message = $this->getMessage();

        $chat = $message->getChat();
        $user = $message->getFrom();
        $text = trim($message->getText(true));
        $chat_id = $chat->getId();
        $user_id = $user->getId();

        if ($message) {
            $chat = $message->getChat();
            $user = $message->getFrom();
            $text = trim($message->getText(true));
            $chat_id = $chat->getId();
            $user_id = $user->getId();
            $message_id = $message->getMessageId();
        }elseif ($callback_query) {
            $message = $callback_query->getMessage();
            $message_id = $message->getMessageId();
            $chat = $message->getChat();
            $user = $callback_query->getFrom();
            $text = $callback_query->getData();
            $chat_id = $chat->getId();
            $user_id = $user->getId();
            $text_callback = $callback_query->getData();
        }

        //Preparing Response
        $data = [
            'chat_id' => $chat_id,
        ];

        if ($chat->isGroupChat() || $chat->isSuperGroup()) {
            //reply to message id is applied by default
            //Force reply is applied by default so it can work with privacy on
            $data['reply_markup'] = Keyboard::forceReply(['selective' => true]);
        }


        //cache data from the tracking session if any


        $result = Request::emptyResponse();

        $db = DBikastenbot::getInstance();


        $r = $db->getUserLang($user_id);
        $lang = $r[0]['language'];

        $res = $db->getSystemMessageById(1, $lang);
        $texto = $res[$lang];

        $data['text'] =$texto;
        $data['parse_mode'] = 'HTML';
        $questions = $db->getFAQquestions($lang);

        $buttons =[];
        foreach ($questions as $ques){
            $boton =array('text' => $ques[$lang], 'callback_data' => $ques['question_id']);
            array_push( $buttons,$boton );
        }

        $inline_keyboard = new InlineKeyboard([]);
        $i=0;
        while (isset($buttons[$i])) {
            $inline_keyboard->addRow($buttons[$i]);
            $i++;
        }
        $data['reply_markup'] = $inline_keyboard;
        $result = Request::sendMessage($data);





                return $result;
    }
}
