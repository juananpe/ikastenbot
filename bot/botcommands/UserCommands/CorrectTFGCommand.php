<?php
/**
 * Created by PhpStorm.
 * User: amaia
 * Date: 20/06/18
 * Time: 17:25.
 */

namespace Longman\TelegramBot\Commands\UserCommands;

use App\Legacy\DBikastenbot;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Request;

class CorrectTFGCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'correctTFG';

    /**
     * @var string
     */
    protected $description = 'Corregir la ultima versión del TFG';

    /**
     * @var string
     */
    protected $usage = '/correctTFG';

    /**
     * @var string
     */
    protected $version = '0.0.1';

    /**
     * @var bool
     */
    protected $need_mysql = true;

    /**
     * Conversation Object.
     *
     * @var \Longman\TelegramBot\Conversation
     */
    protected $conversation;

    public function execute()
    {
        $callback_query = $this->getUpdate()->getCallbackQuery();
        $message = $this->getMessage();

        if ($message) {
            $chat = $message->getChat();
            $user = $message->getFrom();
            $text = trim($message->getText(true));
            $chat_id = $chat->getId();
            $user_id = $user->getId();
            $message_id = $message->getMessageId();
        } elseif ($callback_query) {
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

        //conseguir la ultima version del TFG del usuario
        $tfg = $db->getLastTFGversionbyUser($user_id);

        //State machine
        //Entrypoint of the machine state if given by the track
        //Every time a step is achieved the track is updated
        switch ($state) {
            case 0:
                if ('' === $text || !in_array($text, ['SI', 'NO'])) {
                    $r = $db->getUserLang($user_id);
                    $user_lang = $r[0]['language'];
                    $notes['user_lang'] = $user_lang;
                    $notes['state'] = 0;
                    $notes['last_TFGid'] = $tfg['id'];
                    $this->conversation->update();

                    if (!$tfg) {
                        $lang = $notes['user_lang'];
                        $res = $db->getSystemMessageById(19, $lang);
                        $texto = $res[$lang];
                        $data['text'] = $texto;
                        $data['reply_markup'] = Keyboard::remove(['selective' => true]);

                        $this->conversation->stop();

                        $result = Request::sendMessage($data);

                        break;
                    }
                    $lang = $notes['user_lang'];

                    $res = $db->getSystemMessageById(20, $lang);
                    $texto = $res[$lang];
                    $data['text'] = $texto.$tfg['date'];

                    $res = $db->getSystemMessageById(21, $lang);
                    $texto = $res[$lang];
                    $data['text'] = $data['text'].PHP_EOL.$texto;

                    $keyboard = new Keyboard('SI', 'NO');
                    $keyboard->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(false)
                        ;

                    $data['reply_markup'] = $keyboard;

                    $result = Request::sendMessage($data);

                    break;
                }

                $notes['corregir'] = $text;
                $text = '';
                if ('NO' == $notes['corregir']) {
                    $lang = $notes['user_lang'];
                    $res = $db->getSystemMessageById(22, $lang);
                    $texto = $res[$lang];
                    $data['text'] = $texto;
                    $data['reply_markup'] = Keyboard::remove(['selective' => true]);

                    $this->conversation->stop();

                    $result = Request::sendMessage($data);

                    break;
                }

            // no break
            case 1:
                if ('' === $text) {
                    if (!$tfg['correction']) {
                        if ('es' == $tfg['lang']) {
                            $notes['state'] = 1;

                            $this->conversation->update();

                            $lang = $notes['user_lang'];

                            $res = $db->getSystemMessageById(23, $lang);
                            $texto = $res[$lang];

                            $data['text'] = $texto;
                            $data['reply_markup'] = Keyboard::remove(['selective' => true]);

                            $result = Request::sendMessage($data);

                            //corregir (llamada API)
                            $tfg['correction'] = $this->corregir($tfg, $notes);
                        } else {
                            $lang = $notes['user_lang'];

                            $res = $db->getSystemMessageById(24, $lang);
                            $texto = $res[$lang];

                            $data['text'] = $texto;
                            $data['reply_markup'] = Keyboard::remove(['selective' => true]);

                            $this->conversation->stop();

                            $result = Request::sendMessage($data);

                            break;
                        }
                    }
                }

            // no break
            case 2:
                $notes['state'] = 2;
                if (!isset($notes['pagina'])) {
                    $notes['pagina'] = 1;
                }
                $this->conversation->update();

                $this->mostrarCorreccion($tfg['correction'], $notes['pagina'], $notes);
        }

        return $result;
    }

    /**
     * Command execute method.
     *
     * @param mixed $tfg
     * @param mixed $notes
     *
     * @throws \Longman\TelegramBot\Exception\TelegramException
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     */
    private function corregir($tfg, $notes)
    {
        $txt_path = $tfg['txtPath'];
        $fitx = fopen($txt_path, 'r') or die('Unable to open file!');
        $text = fread($fitx, filesize($txt_path));
        fclose($fitx);

        $data = ['language' => 'es', 'text' => $text];
        $data = http_build_query($data);
        $context_options = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded'
                    .'Accept: application/json',
                'content' => $data,
            ],
        ];

        $context = stream_context_create($context_options);
//        $fp = fopen('https://languagetool.org/api/v2/check', 'r', false, $context);

        $fp = fopen('http://localhost:8081/v2/check', 'r', false, $context);

        if ($fp) {
            $contents = stream_get_contents($fp);
            //        echo $contents;

            //        $json = json_decode($contents, true);

            //         $json_string = json_encode($json, JSON_PRETTY_PRINT);
            //
            //        foreach ($json['matches'] as $error){
            //            echo $error['message'];
            //        }

            //Guardar correccion en txt
            $path_correction = dirname($txt_path).'/correction.txt';
            $fitx = fopen($path_correction, 'wr') or die('Unable to open file!');
            fwrite($fitx, $contents);
            fclose($fitx);

            //actualizar la BD
            $db = DBikastenbot::getInstance();

            $db->updateCorrectionTFGversion($tfg['id'], $path_correction);

            return $path_correction;
        }
        echo 'Error, posiblemente servidor de languagetool no iniciado';
        $message = $this->getMessage();
        $chat = $message->getChat();
        $user = $message->getFrom();
        $text = trim($message->getText(true));
        $chat_id = $chat->getId();
        $user_id = $user->getId();
        $message_id = $message->getMessageId();

        $data = [];
        $db = DBikastenbot::getInstance();
        $lang = $notes['user_lang'];

        $res = $db->getSystemMessageById(18, $lang);
        $texto = $res[$lang];
        $data['text'] = $texto;
        $data['chat_id'] = $chat_id;

        $this->conversation->stop();
        Request::sendMessage($data);
    }

    private function mostrarCorreccion($correction_path, $pagina, $notes)
    {
        $entradas_por_pagina = 3;

        if ($correction_path) {
            $callback_query = $this->getUpdate()->getCallbackQuery();
            $message = $this->getMessage();
            $data = [];

            //si pagina negativa (boton terminar) devolver mensaje
            if ($pagina < 0) {
                $message = $callback_query->getMessage();
                $message_id = $message->getMessageId();
                $chat = $message->getChat();
                $user = $callback_query->getFrom();
                $text = $callback_query->getData();
                $chat_id = $chat->getId();
                $user_id = $user->getId();
                $text_callback = $callback_query->getData();
                echo 'borrando....'.PHP_EOL;
                $data['parse_mode'] = 'HTML';
                $data['chat_id'] = $chat_id;
                $data['message_id'] = $message_id;
                $data['text'] = $message->getText();
                //            $data['reply_markup'] = Keyboard::remove(['selective' => true]);

                $this->conversation->stop();

                return Request::editMessageText($data);
            }

            //echo "PATH".PHP_EOL.$correction_path.PHP_EOL;
            $fitx = fopen($correction_path, 'r') or die('Unable to open file!');
            $correction = fread($fitx, filesize($correction_path));
            fclose($fitx);
            //        echo $correction;

            $json_correction = json_decode($correction, true);
            $errors = $json_correction['matches'];
            $db = DBikastenbot::getInstance();
            $lang = $notes['user_lang'];

            $res = $db->getSystemMessageById(34, $lang);
            $texto_db = $res[$lang];
            $texto = '<b>'.$texto_db.'</b>'.PHP_EOL.PHP_EOL;
            $data['parse_mode'] = 'HTML';

            //        //Para ver que poner en negrita
            //        foreach ($errors as $error){
            //            $context = $error['context'];
            //            $texto_frase = $context['text'];
            //            $offset = $context['offset'];
            //            $length = $context['length'];
            //            echo PHP_EOL.$texto_frase;
            //            echo PHP_EOL."Offset: ".$offset." Length: ".$length." Texto: ".substr($texto_frase,$offset,$length);
            //        }

            $i = 0;
            while ($i < 3 && ((($pagina - 1) * $entradas_por_pagina + $i) < count($errors))) {
                //conseguir $entradas_por_pagina errores (indice con la pagina)
                $error = $errors[($pagina - 1) * $entradas_por_pagina + $i];
                $texto_error = '<b>'.$error['message'].'</b>'.PHP_EOL;
                $context = $error['context'];
                $texto_frase = $context['text'];
                $offset = $context['offset'];
                $length = $context['length'];
                //            preg_match_all('[áéíóúÁÉÍÓÚÑñ¿¡Üü]',substr($texto_frase,0,$offset),$matches);
                //            $offset = $offset + count($matches);
                $texto_negrita = mb_substr($texto_frase, 0, $offset, 'utf-8').'<b>**'.mb_substr($texto_frase, $offset, $length, 'utf-8').'**</b>'.mb_substr($texto_frase, $offset + $length, null, 'utf-8').PHP_EOL;
                //            $texto_negrita = substr($texto_frase,0,$offset).substr($texto_frase,$offset,$length).substr($texto_frase,$offset+$length).PHP_EOL;
                $texto_error .= 'Contexto: '.$texto_negrita;
                $texto .= $texto_error.PHP_EOL;
                ++$i;
                //            echo "INDICE :".(($pagina-1)*$entradas_por_pagina+$i).PHP_EOL.PHP_EOL;
                //            echo substr($texto_frase,$offset+$length-1);
                //            echo mb_detect_encoding(substr($texto_frase,$offset+$length-1));
            }
            $data['text'] = $texto;

            //crear teclado
            $paginas_pasar = 5;
            $paginas_anteriores = max(1, $pagina - $paginas_pasar);
            $paginas_siguientes = min((ceil(count($errors) / $entradas_por_pagina)), $pagina + $paginas_pasar);
            $inline_keyboard = new InlineKeyboard([
                ['text' => '<<'.$paginas_anteriores, 'callback_data' => '{"mostrar_pagina":'.$entradas_por_pagina.'}'],
                ['text' => '<'.max(1, $pagina - 1), 'callback_data' => '{"mostrar_pagina":'.max(1, $pagina - 1).'}'],
                ['text' => $pagina, 'callback_data' => '{"mostrar_pagina":'.$pagina.'}'],
                ['text' => min((ceil(count($errors) / $entradas_por_pagina)), $pagina + 1).'>', 'callback_data' => '{"mostrar_pagina":'.min((ceil(count($errors) / $entradas_por_pagina)), $pagina + 1).'}'],
                ['text' => $paginas_siguientes.'>>', 'callback_data' => '{"mostrar_pagina":'.$paginas_siguientes.'}'],
            ], [
                ['text' => 'Terminar', 'callback_data' => '{"mostrar_pagina":-1}'],
            ]);
            $data['reply_markup'] = $inline_keyboard;

            if ($message) {
                $chat = $message->getChat();
                $user = $message->getFrom();
                $text = trim($message->getText(true));
                $chat_id = $chat->getId();
                $user_id = $user->getId();
                $message_id = $message->getMessageId();

                $data['chat_id'] = $chat_id;

                return Request::sendMessage($data);
            }
            if ($callback_query) {
                $message = $callback_query->getMessage();
                $message_id = $message->getMessageId();
                $chat = $message->getChat();
                $user = $callback_query->getFrom();
                $text = $callback_query->getData();
                $chat_id = $chat->getId();
                $user_id = $user->getId();
                $text_callback = $callback_query->getData();

                $data['chat_id'] = $chat_id;
                $data['message_id'] = $message_id;

                return Request::editMessageText($data);
            }
        }
    }
}
