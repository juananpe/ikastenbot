<?php
/**
 * Created by PhpStorm.
 * User: amaia
 * Date: 18/06/18
 * Time: 13:04
 */

namespace Longman\TelegramBot\Commands\UserCommands;



use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Entities\PhotoSize;
use Longman\TelegramBot\Request;
use MikelAlejoBR\TelegramBotGanttProject\Utils\DBikastenbot;


class registerTFGCommand extends UserCommand
{

    /**
     * @var string
     */
    protected $name = 'registerTFG';

    /**
     * @var string
     */
    protected $description = 'Registrar un TFG';

    /**
     * @var string
     */
    protected $usage = '/registerTFG';

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


        $idiomas = ['es','eus'];

        $message = $this->getMessage();

        $chat    = $message->getChat();
        $user    = $message->getFrom();
        $text    = trim($message->getText(true));
        $chat_id = $chat->getId();
        $user_id = $user->getId();

        //Preparing Response
        $data = [
            'chat_id' => $chat_id,
        ];

        if ($chat->isGroupChat() || $chat->isSuperGroup()) {
            //reply to message id is applied by default
            //Force reply is applied by default so it can work with privacy on
            $data['reply_markup'] = Keyboard::forceReply(['selective' => true]);
        }

        //Conversation start
        $this->conversation = new Conversation($user_id, $chat_id, $this->getName());

        $notes = &$this->conversation->notes;
        !is_array($notes) && $notes = [];

        //cache data from the tracking session if any
        $state = 0;
        if (isset($notes['state'])) {
            $state = $notes['state'];
        }

        $result = Request::emptyResponse();

        $db = DBikastenbot::getInstance();



        //State machine
        //Entrypoint of the machine state if given by the track
        //Every time a step is achieved the track is updated
        switch ($state) {
            case 0:
                if ($text === '') {
                    $notes['state'] = 0;
                    $this->conversation->update();

                   //comprobar si el usuario tiene un TFG registrado
                    $tfg = $db->getTFGbyUser($user_id);

                    if ($tfg) {
                        $r = $db->getUserLang($user_id);
                        $lang = $r[0]['language'];

                        $res = $db->getSystemMessageById(13, $lang);
                        $texto = $res[$lang];
                        $data['text'] = $texto;
                        $data['reply_markup'] = Keyboard::remove(['selective' => true]);

                        $this->conversation->stop();

                        $result = Request::sendMessage($data);
                        break;
                    }else{
                        $r = $db->getUserLang($user_id);
                        $lang = $r[0]['language'];

                        $res = $db->getSystemMessageById(14, $lang);
                        $texto = $res[$lang];
                        $data['text'] = $texto;
                        $data['reply_markup'] = Keyboard::remove(['selective' => true]);


                        $result = Request::sendMessage($data);
                        break;
                    }
                }

                $notes['name'] = $text;
                $text          = '';

            // no break
            case 1:
                if ($text === '' || !in_array($text,$idiomas)) {
                    $notes['state'] = 1;
                    $this->conversation->update();

                    $r = $db->getUserLang($user_id);
                    $lang = $r[0]['language'];

                    $res = $db->getSystemMessageById(15, $lang);
                    $texto = $res[$lang];
                    $data['text'] = $texto;
//                    $keyboard = new Keyboard('es','eus');
                    $keyboard = new Keyboard([]);
                    $i=0;
                    while (isset($idiomas[$i])) {
                        $keyboard->addRow($idiomas[$i]);
                        $i++;
                    }
                    $keyboard->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(false);

                    $data['reply_markup'] = $keyboard;

                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['lang'] = $text;

            // no break
            case 2:
                $this->conversation->update();


                $anadido = $db->registerTFG($user_id,$notes['name'],$notes['lang']);
                if($anadido) {
                    $r = $db->getUserLang($user_id);
                    $lang = $r[0]['language'];

                    $res = $db->getSystemMessageById(16, $lang);
                    $texto = $res[$lang];
                    $data['text'] = $texto;
                }else{
                    $r = $db->getUserLang($user_id);
                    $lang = $r[0]['language'];

                    $res = $db->getSystemMessageById(17, $lang);
                    $texto = $res[$lang];
                    $data['text'] = $texto;
                }

                $data['reply_markup'] = Keyboard::remove(['selective' => true]);
                $this->conversation->stop();


                $result = Request::sendMessage($data);
                break;
        }




        return $result;
    }
}
