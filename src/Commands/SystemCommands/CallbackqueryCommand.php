<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Conversation;
use MikelAlejoBR\TelegramBotGanttProject\Utils\DBikastenbot;


/**
 * Callback query command
 *
 * This command handles all callback queries sent via inline keyboard buttons.
 *
 * @see InlinekeyboardCommand.php
 */
class CallbackqueryCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'callbackquery';

    /**
     * @var string
     */
    protected $description = 'Reply to callback query';

    /**
     * @var string
     */
    protected $version = '1.1.1';

    /**
     * Command execute method
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */

    private function insertDates($text, $dates){
        $db = DBikastenbot::getInstance();
        $fechas=$db->getNextDate($dates);
//            $texto = str_replace("##", $fechas[0]['to'], $text);
        $texto = $text . " ". $fechas['to'];
        return $texto;
    }


    public function execute()
    {
        $update            = $this->getUpdate();
        $callback_query    = $this->getCallbackQuery();
        $callback_query_id = $callback_query->getId();
        $callback_data     = $callback_query->getData();

        $user_id = $callback_query->getFrom()->getId();
        $chat_id = $callback_query->getMessage()->getChat()->getId();

        $data = [
            'chat_id' => $chat_id,
        ];

        if(is_numeric($callback_data)){
            $db = DBikastenbot::getInstance();

            $r = $db->getUserLang($user_id);
            $lang = $r[0]['language'];

            $res = $db->getResponseByQuestionID($callback_data);

            $texto = '<b>'.$db->getQuestionText($callback_data, $lang)[$lang].'</b>'. PHP_EOL. PHP_EOL;

            $result =null;
            foreach ($res as $r) {
                $text_id = $r['text'];
                if(is_numeric($text_id)){
                    $text_respon=$db->getMessageByID($text_id, $lang);

                    $texto .= $text_respon[$lang];

                    if($r['date']!=null){
                        $texto=$this->insertDates($texto,$r['date']);
                    }


                    $data['parse_mode'] = 'HTML';
                    $data['text'] = $texto;

                    $result = Request::sendMessage($data);
                    $texto='';
                }

                if($r['photo']!=null){
                    $data['photo'] = Request::encodeFile($this->telegram->getUploadPath() . '/' . $r['photo']);

//                $texto = $r[$lang];
//                $data['parse_mode'] = 'HTML';
//                $data['caption'] = $texto;
                    $result = Request::sendPhoto($data);

                }

                if($r['video']!=null){
                    $data['video'] = Request::encodeFile($this->telegram->getUploadPath() . '/' . $r['video']);


                    $result = Request::sendVideo($data);

                }

                if($r['document']!=null){
                    $data['document'] = Request::encodeFile($this->telegram->getUploadPath() . '/' . $r['document']);


//                $data['parse_mode'] = 'HTML';
                    $result = Request::sendDocument($data);

                }

            }


            return $result;
        }else{
//            echo "CAllback".PHP_EOL;
            $json = json_decode($callback_data,true);
            if(isset($json['mostrar_pagina'])){
//                echo "pagina: ".$json['mostrar_pagina'].PHP_EOL;
                $pagina =$json['mostrar_pagina'];

                    $this->conversation = new Conversation($user_id, $chat_id, "correctTFG");
                    $this->conversation->notes['pagina'] = $pagina;
                    $this->conversation->update();
                    return $this->getTelegram()->executeCommand("correctTFG", $update);

            }
        }

    }
}
