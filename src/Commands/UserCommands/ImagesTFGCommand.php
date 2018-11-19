<?php
/**
 * Created by PhpStorm.
 * User: amaia
 * Date: 27/06/18
 * Time: 16:36
 */

namespace Longman\TelegramBot\Commands\UserCommands;


use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Entities\PhotoSize;
use Longman\TelegramBot\Request;
use TelegramBotGanttProject\Utils\DBikastenbot;


class ImagesTFGCommand extends UserCommand
{

    /**
     * @var string
     */
    protected $name = 'imagesTFG';

    /**
     * @var string
     */
    protected $description = 'Comprueba si las imágenes son originales';

    /**
     * @var string
     */
    protected $usage = '/imagesTFG';

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

    private $maxResultPerImage = 4;
    private $maxImagesToShow = 4;
    private $minScore = 0.9;

    private function getImagesFromPDF($tfg,$user_id){
        //lamada a la API y guardar en la base de datos
        //devuelve false si ocurre algun error (como que el archivo esté protegido por contraseña)


        $db= DBikastenbot::getInstance();
        $pathImages = $this->telegram->getDownloadPath() .'/'. $user_id . '/'.$tfg['version']. '/images'  ;
//        echo $pathImages;
//        error_log( dirname($pathImages) . "\n" , 3, "/tmp/error.log");

        if (!is_dir($pathImages)) {
//            error_log( dirname($pathImages) . "\n" , 3, "/tmp/error.log");
            mkdir($pathImages, 0777, true);
        }

        $tfg_pdf = $tfg['pdfPath'];
        exec("pdfimages -j $tfg_pdf $pathImages/image", $output, $return_var);
        if($return_var==0){
            //actulizar TFGversion DB


            $db->updateImagesPathTFGversion($tfg['id'],$pathImages);

            $files = array_diff(scandir($pathImages), array('.', '..'));
            foreach ($files as $file){
                $imagePath =$pathImages.'/'.$file;
                $hash = md5_file($imagePath);
                $result =$db->existImageInVersion($tfg['id'],$hash);
//                echo PHP_EOL.$imagePath." HASH: ".$hash;
                if(!$result){
//                    echo PHP_EOL."HA ENTRADO";
                    //si la imagen no es repetida llamar a la API y guardar en la BD
                    $image = file_get_contents($imagePath);
                    $image64 = base64_encode($image);

                    $postData = array(
                        'requests' => array(
                            'image'=>array(
                                'content'=> "$image64"
                            ),
                            'features'=>array(
                                "type"=> "WEB_DETECTION",
                                "maxResults" => $this->maxResultPerImage,
                            )
                        ),
                    );

                    $google_apiKey = getenv('GOOGLE_API_KEY');

                    $ch = curl_init('https://vision.googleapis.com/v1/images:annotate?key='.$google_apiKey);
                    curl_setopt_array($ch, array(
                        CURLOPT_POST => TRUE,
                        CURLOPT_RETURNTRANSFER => TRUE,
                        CURLOPT_HTTPHEADER => array(
                            'Content-Type: application/json'
                        ),
                        CURLOPT_POSTFIELDS => json_encode($postData)
                    ));

                    // Send the request
                    $response = curl_exec($ch);
                    if( $response)
                    {

                        $db->insertImage($tfg['id'],$hash,$imagePath,$response);
                    }
                    curl_close($ch);


                }
            }
        }else{
            echo "Error al sacar las imágnes del PDF";
        }

    }


    private function mostrarImagenes($last_TFGid){
        $message = $this->getMessage();

        $chat    = $message->getChat();
        $user    = $message->getFrom();
        $text    = trim($message->getText(true));
        $chat_id = $chat->getId();
        $user_id = $user->getId();


        $data['chat_id']= $chat_id;

//                $data['parse_mode'] = 'HTML';

        $db = DBikastenbot::getInstance();
        $r = $db->getUserLang($user_id);
        $lang = $r[0]['language'];

        $res = $db->getSystemMessageById(25, $lang);
        $texto = $res[$lang];
        $data['text'] = $texto;
        $result = Request::sendMessage($data);

        $data = [];

        $images = $db->getImagesByTFGid($last_TFGid);

        $i = 0;
        $imagesSent = 0;

        while($i<count($images) && $imagesSent<$this->maxImagesToShow) {
            $text = '';
            $image = $images[$i];
            $api_result = $image['api_result'];
//            echo $api_result.PHP_EOL.PHP_EOL;
            $api_result_json = json_decode($api_result, true);
            if (isset($api_result_json['responses'])){
                $responses = $api_result_json['responses'][0];
                $web_detection = $responses['webDetection'];
                $webEntities = $web_detection['webEntities'];
//                echo PHP_EOL."IMAGE: ".$image['path']."   SCORE:  ".$webEntities[0]['score'];
                if ($webEntities[0]['score'] >= $this->minScore) {
    //                echo PHP_EOL."score >>";
                    if ($webEntities[0]['description']) {
    //                    echo " description  ";
                        $res = $db->getSystemMessageById(26, $lang);
                        $texto = $res[$lang];
                        $text .= $texto . $webEntities[0]['description'] . PHP_EOL . PHP_EOL;
                    }
                    if (isset($web_detection['pagesWithMatchingImages'])) {
//                        echo " pagesWithMatchingImages  ";
                        $res = $db->getSystemMessageById(27, $lang);
                        $texto = $res[$lang];
                        $text .= $texto . PHP_EOL . PHP_EOL;
                        $pagesWithMatchingImages = $web_detection['pagesWithMatchingImages'];
                        for ($cont = 0; $cont < min(count($pagesWithMatchingImages), $this->maxImagesToShow); $cont++) {
                            if (isset($pagesWithMatchingImages[$cont]['url'])) {
                                $text .= $pagesWithMatchingImages[$cont]['url'] . PHP_EOL . PHP_EOL;
                            }
                        }
//                         echo $text;
                        $data['chat_id'] = $chat_id;
                        $data['photo'] = Request::encodeFile($image['path']);

        //                $data['parse_mode'] = 'HTML';
        //                $data['caption'] = $text;
        //
                        $result = Request::sendPhoto($data);
                        $data = [];
                        $data['chat_id'] = $chat_id;
                        $data['text'] = $text;

                        $result = Request::sendMessage($data);
                        $imagesSent++;
                    } else {

                        if (isset($web_detection['fullMatchingImages'])) {
                            $res = $db->getSystemMessageById(27, $lang);
                            $texto = $res[$lang];
                            $text .= $texto . PHP_EOL . PHP_EOL;
                            $fullMatchingImages = $web_detection['fullMatchingImages'];
                            for ($cont = 0; $cont < min(count($fullMatchingImages), $this->maxImagesToShow); $cont++) {
                                if (isset($fullMatchingImages[$cont]['url'])) {
                                    $text .= $fullMatchingImages[$cont]['url'] . PHP_EOL . PHP_EOL;
                                }
                            }
//                            echo $text;
                            $data['chat_id'] = $chat_id;
                            $data['photo'] = Request::encodeFile($image['path']);

            //                $data['parse_mode'] = 'HTML';
            //                $data['caption'] = $text;
            //
                            $result = Request::sendPhoto($data);
                            $data = [];
                            $data['chat_id'] = $chat_id;
                            $data['text'] = $text;

                            $result = Request::sendMessage($data);
                            $imagesSent++;
                        }
                    }

                }
            }
            $i++;
        }
        if($imagesSent==0){
            $data['chat_id']= $chat_id;

//                $data['parse_mode'] = 'HTML';
            $res = $db->getSystemMessageById(28, $lang);
            $texto = $res[$lang];
            $data['text'] = $texto;
            $result = Request::sendMessage($data);
        }

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

        //conseguir la ultima version del TFG del usuario
        $tfg = $db->getLastTFGversionbyUser($user_id);


        //State machine
        //Entrypoint of the machine state if given by the track
        //Every time a step is achieved the track is updated
        switch ($state) {
            case 0:
                if ($text === ''|| !in_array($text, ['SI', 'NO'])) {
                    $notes['state'] = 0;
                    $notes['last_TFGid'] = $tfg['id'];
                    $this->conversation->update();

                    if (!$tfg) {
                        $r = $db->getUserLang($user_id);
                        $lang = $r[0]['language'];

                        $res = $db->getSystemMessageById(29, $lang);
                        $texto = $res[$lang];
                        $data['text'] = $texto;
                        $data['reply_markup'] = Keyboard::remove(['selective' => true]);

                        $this->conversation->stop();

                        $result = Request::sendMessage($data);
                        break;
                    }else{
                        $r = $db->getUserLang($user_id);
                        $lang = $r[0]['language'];

                        $res = $db->getSystemMessageById(30, $lang);
                        $texto = $res[$lang];
                        $data['text'] = $texto . $tfg['date'];
                        $r = $db->getUserLang($user_id);
                        $lang = $r[0]['language'];

                        $res = $db->getSystemMessageById(31, $lang);
                        $texto = $res[$lang];
                        $data['text'] = $data['text'] . PHP_EOL. $texto;

                        $keyboard = new Keyboard('SI','NO');
                        $keyboard->setResizeKeyboard(true)
                            ->setOneTimeKeyboard(true)
                            ->setSelective(false);

                        $data['reply_markup'] = $keyboard;

                        $result = Request::sendMessage($data);
                        break;

                    }
                }

                $notes['comprobar'] = $text;
                $text          = '';
                if($notes['comprobar'] =='NO'){
                    $r = $db->getUserLang($user_id);
                    $lang = $r[0]['language'];

                    $res = $db->getSystemMessageById(32, $lang);
                    $texto = $res[$lang];
                    $data['text'] = $texto;
                    $data['reply_markup'] = Keyboard::remove(['selective' => true]);

                    $this->conversation->stop();

                    $result = Request::sendMessage($data);
                    break;
                }

            // no break
            case 1:
                if ($text === '') {
                    $notes['state'] = 1;
                    $this->conversation->update();
                    if(!isset($tfg['imagesPath'])){
//                        echo "  CASE 1 DEntro isset";
                        $r = $db->getUserLang($user_id);
                        $lang = $r[0]['language'];

                        $res = $db->getSystemMessageById(33, $lang);
                        $texto = $res[$lang];
                        $data['text'] = $texto;
                        $data['reply_markup'] = Keyboard::remove(['selective' => true]);


                        $result = Request::sendMessage($data);
                        $this->getImagesFromPDF($tfg,$user_id);
                    }

                    $this->mostrarImagenes($notes['last_TFGid']);
                    $this->conversation->stop();
                }




        }




        return $result;
    }
}
