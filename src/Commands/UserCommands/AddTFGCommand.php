<?php
/**
 * Created by PhpStorm.
 * User: amaia
 * Date: 19/06/18
 * Time: 11:30
 */

namespace Longman\TelegramBot\Commands\UserCommands;


use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\File;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Entities\PhotoSize;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Telegram;
use finfo;
use Spatie\PdfToText\Pdf;
use TelegramBotGanttProject\Utils\DBikastenbot;

class addTFGCommand extends UserCommand
{

    /**
     * @var string
     */
    protected $name = 'addTFG';

    /**
     * @var string
     */
    protected $description = 'Añadir versión de un TFG';

    /**
     * @var string
     */
    protected $usage = '/addTFG';

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



    private function comprobar_extension($filepath){
        /**Extraído de Stack Overflow
        https://stackoverflow.com/questions/33695131/how-to-validate-a-file-type-against-its-extension
         **/

        $mime_types = array(
            'doc' => array('application/msword'),
            'docx' => array('application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
            'pdf' => array('application/pdf'),
            'odt' => array('application/vnd.oasis.opendocument.text'),


        );

        if( strpos( $filepath, '.' ) === false ) {
            // no file extension
            return null;
        }
        // get the file extension
        $fileExt =  pathinfo($filepath, PATHINFO_EXTENSION);
//        echo "File_Exten " . $fileExt.PHP_EOL;

        // get the mime type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $fileMimeType =$finfo->file($filepath);
//        echo "File_MIME " . $fileMimeType.PHP_EOL;

        // now check if filextension is in array and if mimetype for this extension is correct
        if( isset( $mime_types[$fileExt] ) && in_array( $fileMimeType, $mime_types[$fileExt] ) ) {
            // file extension is OK AND mime type matches the file extension
            return $fileExt;
        } else {
            // not passed unknown file type, unknown mime type, or someone tricked you
            return null;
        }


    }


    private function cleanText($texto_original){

        //Juntar las frases que empiezan por minuscula
        $texto_juntado = preg_replace('/(\n)([a-záéíóúñ]+)/u', ' $2',$texto_original);

        //Quitar URLs
        $sin_url = preg_replace('/https?:[^ \n][ \n]/u', '',$texto_juntado);

        //Conseguir las frases
        $letras_frase = 30;
        $filtro = '/([A-Z¡¿][^\.!?\n]{'.$letras_frase.',}[\.!?][ \n])/u';
        $frases = preg_match_all($filtro,$sin_url,$matches);

        $text ='';
        foreach ($matches[0] as $m){
            $text = $text . PHP_EOL.$m;
        }
        return $text;

    }

    private function pdf2text($pdf_path,$user_id,$version){

        //consigue texto
        $text = Pdf::getText($pdf_path);

        //guardar el txt
        $partes_ruta = pathinfo($pdf_path);
        $dir_name =  $partes_ruta['dirname'];
        $file_name =  $partes_ruta['filename'];

        $txt_path = $dir_name.'/'.$file_name.'.txt';

        $new_text = $this->cleanText($text);

        $myfile = fopen($txt_path, "wr") or die("Unable to open file!");
        fwrite($myfile, $new_text);
        fclose($myfile);

        //actualizar la BD
        $db = DBikastenbot::getInstance();

        $db->updateTextTFGversion($txt_path,$pdf_path);

    }





    public function execute()
    {
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
                if ($message->getDocument() === null) {
                    $notes['state'] = 0;
                    $this->conversation->update();

                    //comprobar si el usuario tiene un TFG registrado
                    $tfg = $db->getTFGbyUser($user_id);

                    if (!$tfg) {
                        $r = $db->getUserLang($user_id);
                        $lang = $r[0]['language'];

                        $res = $db->getSystemMessageById(7, $lang);
                        $texto = $res[$lang];
                        $data['text'] = $texto;
                        $data['reply_markup'] = Keyboard::remove(['selective' => true]);

                        $this->conversation->stop();

                        $result = Request::sendMessage($data);
                        break;
                    }else{
                        $r = $db->getUserLang($user_id);
                        $lang = $r[0]['language'];

                        $res = $db->getSystemMessageById(8, $lang);
                        $texto = $res[$lang];
                        $data['text'] = $texto;
                        $data['reply_markup'] = Keyboard::remove(['selective' => true]);


                        $result = Request::sendMessage($data);
                        break;
                    }
                }

                $document = $message->getDocument();

                $notes['document_id'] = $document->getFileId();
                $text          = '';


                $response = Request::getFile(['file_id' => $document->getFileId()]);
                if ($response->isOk()) {
                    /** @var File $photo_file */
                    $file = $response->getResult();

                   if( Request::downloadFile($file)){

                       $path_local =$this->telegram->getDownloadPath() . '/' . $file->getFilePath();
                       if(is_readable($path_local)){
                           $tipo = $this->comprobar_extension($path_local); //conseguir el tipo (extension) del archivo
//                           echo "File_TYPE " . $tipo.PHP_EOL;
                           switch ($tipo){
                                case "doc":
                                case "docx":
                                case "odt":
//                                    echo "Convertir a pdf";
                                       $hash = md5_file($path_local);
                                       $versionAnterior = $db->existVersion($user_id,$hash);
                                       if($versionAnterior){
                                           $r = $db->getUserLang($user_id);
                                           $lang = $r[0]['language'];

                                           $res = $db->getSystemMessageById(9, $lang);
                                           $texto = $res[$lang];
                                           $data['text'] = $texto. $versionAnterior['date'];
                                           $data['reply_markup'] = Keyboard::remove(['selective' => true]);

                                           $this->conversation->stop();

                                           $result = Request::sendMessage($data);
                                       }else{
                                           //añadir la version a la base de datos y mover el archivo
                                           $lastVersion = $db->getLastVersionNumber($user_id);
                                           $newPath = $this->telegram->getDownloadPath() .'/'. $user_id . '/'.($lastVersion+1). '/' .$hash.'.'.$tipo ;
                                           if (!is_dir(dirname($newPath))) {
                                               mkdir(dirname($newPath), 0777, true);
                                           }
                                           rename($path_local,$newPath);
                                           exec("unoconv -f pdf $newPath", $output, $return_var);
                                           if($return_var==0){
                                               $partes_ruta = pathinfo($newPath);
                                               $dir_name =  $partes_ruta['dirname'];
                                               $file_name =  $partes_ruta['filename'];

                                               $pdfpath =$dir_name.'/'.$file_name.'.pdf';
                                               if($db->registerTFGversion($user_id,$lastVersion+1,$hash,$newPath,$pdfpath)){
                                                   try{
                                                       $this->pdf2text($pdfpath,$user_id,$lastVersion+1);
                                                       $r = $db->getUserLang($user_id);
                                                       $lang = $r[0]['language'];

                                                       $res = $db->getSystemMessageById(10, $lang);
                                                       $texto = $res[$lang];
                                                       $data['text'] = $texto;

                                                   }catch (Exception $e){
                                                       $r = $db->getUserLang($user_id);
                                                       $lang = $r[0]['language'];

                                                       $res = $db->getSystemMessageById(11, $lang);
                                                       $texto = $res[$lang];
                                                       $data['text'] = $texto;
                                                   }

                                                   $data['reply_markup'] = Keyboard::remove(['selective' => true]);

                                                   $this->conversation->stop();

                                                   $result = Request::sendMessage($data);
                                               }
                                           }

                                       }
                                       break;

                                case "pdf":
//                                    echo "comprobar md5";

                                    $hash = md5_file($path_local);
                                    $versionAnterior = $db->existVersion($user_id,$hash);
                                    if($versionAnterior){
                                        $r = $db->getUserLang($user_id);
                                        $lang = $r[0]['language'];

                                        $res = $db->getSystemMessageById(9, $lang);
                                        $texto = $res[$lang];
                                        $data['text'] = $texto. $versionAnterior['date'];
                                        $data['reply_markup'] = Keyboard::remove(['selective' => true]);

                                        $this->conversation->stop();

                                        $result = Request::sendMessage($data);
                                    }else{
                                        //añadir la version a la base de datos y mover el archivo
                                        $lastVersion = $db->getLastVersionNumber($user_id);
                                        $newPath = $this->telegram->getDownloadPath() .'/'. $user_id . '/'.($lastVersion+1). '/' .$hash.'.'.$tipo ;
                                        if (!is_dir(dirname($newPath))) {
                                            mkdir(dirname($newPath), 0777, true);
                                        }
                                        rename($path_local,$newPath);
                                        if($db->registerTFGversion($user_id,$lastVersion+1,$hash,$newPath,$newPath)){
                                            try{
                                                $this->pdf2text($newPath,$user_id,$lastVersion+1);

                                                $r = $db->getUserLang($user_id);
                                                $lang = $r[0]['language'];

                                                $res = $db->getSystemMessageById(10, $lang);
                                                $texto = $res[$lang];
                                                $data['text'] = $texto;

                                            }catch (Exception $e){
                                                $r = $db->getUserLang($user_id);
                                                $lang = $r[0]['language'];

                                                $res = $db->getSystemMessageById(11, $lang);
                                                $texto = $res[$lang];
                                                $data['text'] = $texto;
                                            }

                                            $data['reply_markup'] = Keyboard::remove(['selective' => true]);

                                            $this->conversation->stop();

                                            $result = Request::sendMessage($data);
                                        }
                                    }

                                    break;
                                default:
                                    $r = $db->getUserLang($user_id);
                                    $lang = $r[0]['language'];

                                    $res = $db->getSystemMessageById(12, $lang);
                                    $texto = $res[$lang];
                                    $data['text'] = $texto;
                                    $data['reply_markup'] = Keyboard::remove(['selective' => true]);

                                    $this->conversation->stop();

                                    $result = Request::sendMessage($data);
                                    break;
                            }

                       }
                   }

                }


                $this->conversation->stop();
                break;


        }




        return $result;
    }

}