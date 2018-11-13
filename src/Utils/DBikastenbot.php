<?php
/**
 * Created by PhpStorm.
 * User: amaia
 * Date: 23/04/18
 * Time: 0:16
 */

namespace TelegramBotGanttProject\Utils;

use Longman\TelegramBot\Exception\TelegramException;
use \PDO;
use Symfony\Component\Dotenv\Dotenv;

class DBikastenbot
{



    static protected $pdo;
    private static $instance = null;

    /**
     * DBikastenbot constructor.
     */
    public function __construct()
    {
        $dotenv = new Dotenv();
        $dotenv->load(__DIR__.'/../../.env');

        $charset    = getenv('MYSQL_CHARSET');
        $host       = getenv('MYSQL_HOST');
        $db         = getenv('MYSQL_DATABASE_NAME');
        $user       = getenv('MYSQL_USERNAME');
        $pass       = getenv('MYSQL_USER_PASSWORD');

        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        $opt = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            ];
          self::$pdo  = new PDO($dsn, $user, $pass, $opt);
    }

    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new DBikastenbot();
        }
        return self::$instance;
    }

    public static function isDbConnected()
    {
        return self::$pdo !== null;

    }

    public function getMessageByTag($tag, $lang){
        if (!self::isDbConnected()) {
            return null;
        }
        try {
            $query = "select $lang from messages_lang where tag= '$tag'";
            $sth = self::$pdo->prepare($query);
            $sth->execute();
            $result = $sth->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new TelegramException($e->getMessage());
        }
        return $result;
    }

    public function getUserLang($user_id){
        if (!self::isDbConnected()) {
            return null;
        }
        try {
            $query = "select language from user where id= $user_id";
            $sth = self::$pdo->prepare($query);
            $sth->execute();
            $result = $sth->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new TelegramException($e->getMessage());
        }
        return $result;
    }

    public function getFAQquestions($lang){
        if (!self::isDbConnected()) {
            return null;
        }
        try {
            $query = "select faq_question.id as 'question_id',$lang from messages_lang,faq_question where `order` is not null and faq_question.text=messages_lang.id order by faq_question.order";
            $sth = self::$pdo->prepare($query);
            $sth->execute();
            $result = $sth->fetchAll(\PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            throw new TelegramException($e->getMessage());
        }
        return $result;
    }

    public function getResponseByQuestionID($id){
        if (!self::isDbConnected()) {
            return null;
        }
        try {
            $query = "select `text`,photo,video,document,`date` from faq_response where question=$id";
//           echo($query);
            $sth = self::$pdo->prepare($query);
            $sth->execute();
            $result = $sth->fetchAll(\PDO::FETCH_ASSOC);
//echo(sizeof($result));

        } catch (PDOException $e) {
            throw new TelegramException($e->getMessage());
        }
        return $result;
    }

    public function getMessageByID($id, $lang){
        if (!self::isDbConnected()) {
            return null;
        }
        try {
            $query = "select $lang from messages_lang where id= $id";
            $sth = self::$pdo->prepare($query);
            $sth->execute();
            $result = $sth->fetch(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new TelegramException($e->getMessage());
        }
        return $result;
    }

    public function getNextDate($tag){
        if (!self::isDbConnected()) {
            return null;
        }
        try {
            $query = "select dates.to from dates where what='$tag' and dates.to>now()  order by dates.to limit 1";
            $sth = self::$pdo->prepare($query);
            $sth->execute();
            $result = $sth->fetch(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new TelegramException($e->getMessage());
        }
        return $result;
    }

    public function getQuestionText($id, $lang){
        if (!self::isDbConnected()) {
            return null;
        }
        try {
            $query = "select $lang from messages_lang,faq_question where faq_question.id=$id and faq_question.text=messages_lang.id";
            $sth = self::$pdo->prepare($query);
            $sth->execute();
            $result = $sth->fetch(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new TelegramException($e->getMessage());
        }
        return $result;
    }


    public function getTFGbyUser($userID){
        if (!self::isDbConnected()) {
            return null;
        }
        try {
            $query = "select * from TFG where TFG.user=$userID";
            $sth = self::$pdo->prepare($query);
            $sth->execute();
            $result = $sth->fetch(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new TelegramException($e->getMessage());
        }
        return $result;
    }

    public function registerTFG($user,$name,$lang){
        if (!self::isDbConnected()) {
            return null;
        }
        try {
            $query = "INSERT into TFG (`user`,`name`,lang) VALUES ($user,'$name','$lang')";
            $sth = self::$pdo->prepare($query);
            $result = $sth->execute();
        } catch (PDOException $e) {
            throw new TelegramException($e->getMessage());
        }
        return $result;
    }

    public function existVersion($user_id,$hash){
        if (!self::isDbConnected()) {
            return null;
        }
        try {
            $query = "select TFGversion.id,`date`,version from TFGversion,TFG where hash='$hash' and TFG.user=$user_id and TFG.id=TFGid";
            $sth = self::$pdo->prepare($query);
            $sth->execute();
            $result = $sth->fetch(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new TelegramException($e->getMessage());
        }
        return $result;
    }

    public function getLastVersionNumber($user_id){
        if (!self::isDbConnected()) {
            return null;
        }
        try {
            $query = "select max(version) as last_version from TFGversion,TFG where TFG.user=$user_id and TFG.id=TFGid";
            $sth = self::$pdo->prepare($query);
            $sth->execute();
            $result = $sth->fetch(\PDO::FETCH_ASSOC);
            $result = $result['last_version'];
        } catch (PDOException $e) {
            throw new TelegramException($e->getMessage());
        }
        return $result;
    }

    public function registerTFGversion($user_id,$version,$hash,$docPath,$pdfPath){
        if (!self::isDbConnected()) {
            return null;
        }
        try {
            $query = "select id from TFG where TFG.user=$user_id";
            $sth = self::$pdo->prepare($query);
            $sth->execute();
            $result = $sth->fetch(\PDO::FETCH_ASSOC);
            $tfgid = $result['id'];

            $query = "INSERT into TFGversion (`version`,`hash`,TFGid, docPath,pdfPath) VALUES ($version,'$hash',$tfgid,'$docPath','$pdfPath')";
            $sth = self::$pdo->prepare($query);
            $result = $sth->execute();
        } catch (PDOException $e) {
            throw new TelegramException($e->getMessage());
        }
        return $result;
    }


    public function getLastTFGversionbyUser($user_id){
        if (!self::isDbConnected()) {
            return null;
        }
        try {
            $query = "select TFGversion.id as id, version,hash, `date`, TFGid, docPath, txtPath, correction,lang,pdfPath,imagesPath  from TFGversion,TFG where TFG.user=$user_id and TFG.id =TFGid and txtPath is not null and txtPath!='' order by version desc limit 1";
            $sth = self::$pdo->prepare($query);
            $sth->execute();
            $result = $sth->fetch(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new TelegramException($e->getMessage());
        }
        return $result;
    }

    public function updateTextTFGversion($txt_path,$pdfPath){
        if (!self::isDbConnected()) {
            return null;
        }
        try {
            $query = "UPDATE TFGversion set txtPath='$txt_path' where pdfPath='$pdfPath'";
            $sth = self::$pdo->prepare($query);
            $result = $sth->execute();
        } catch (PDOException $e) {
            throw new TelegramException($e->getMessage());
        }
        return $result;
    }

    public function getLastTFGversionbyID($id){
        if (!self::isDbConnected()) {
            return null;
        }
        try {
            $query = "select TFGversion.id as id, version,hash, `date`, TFGid, docPath, txtPath, correction,lang   from TFGversion,TFG where  TFGversion.id =$id and TFG.id =TFGid and txtPath is not null and txtPath!='' order by version desc limit 1";
            $sth = self::$pdo->prepare($query);
            $sth->execute();
            $result = $sth->fetch(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new TelegramException($e->getMessage());
        }
        return $result;
    }

    public function updateCorrectionTFGversion($tfgVersion_id,$correction){
        if (!self::isDbConnected()) {
            return null;
        }
        try {
            $query = "UPDATE TFGversion set correction=? where id=?";
            $sth = self::$pdo->prepare($query);
            $result = $sth->execute([$correction,$tfgVersion_id]);
        } catch (PDOException $e) {
            throw new TelegramException($e->getMessage());
        }
        return $result;
    }

    public function existImageInVersion($tfgversion,$hash){
        if (!self::isDbConnected()) {
            return null;
        }
        try {
            $query = "select * from TFGimage where `hash`='$hash' and TFGversion_id =$tfgversion";
            $sth = self::$pdo->prepare($query);
            $sth->execute();
            $result = $sth->fetch(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new TelegramException($e->getMessage());
        }
        return $result;
    }

    public function insertImage($tfgVersion_id,$hash,$path,$api_result){
        if (!self::isDbConnected()) {
            return null;
        }
        try {
            $query = "INSERT into TFGimage (TFGversion_id, hash, path,api_result) VALUES (?,?,?,?)";
            $sth = self::$pdo->prepare($query);
            $result = $sth->execute([$tfgVersion_id,$hash,$path,$api_result]);
        } catch (PDOException $e) {
            throw new TelegramException($e->getMessage());
        }
        return $result;
    }

    public function updateImagesPathTFGversion($tfgVersion_id,$imagesPath){
        if (!self::isDbConnected()) {
            return null;
        }
        try {
            $query = "UPDATE TFGversion set imagesPath=? where id=?";
            $sth = self::$pdo->prepare($query);
            $result = $sth->execute([$imagesPath,$tfgVersion_id]);
        } catch (PDOException $e) {
            throw new TelegramException($e->getMessage());
        }
        return $result;
    }

    public function getImagesByTFGid($TFGid){
        if (!self::isDbConnected()) {
            return null;
        }
        try {
            $query = "select * from TFGimage where TFGversion_id =$TFGid";
            $sth = self::$pdo->prepare($query);
            $sth->execute();
            $result = $sth->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new TelegramException($e->getMessage());
        }
        return $result;
    }

    public function getSystemMessageById($id, $lang){
        if (!self::isDbConnected()) {
            return null;
        }
        try {
            $query = "select $lang from system_message where id=$id";
            $sth = self::$pdo->prepare($query);
            $sth->execute();
            $result = $sth->fetch(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new TelegramException($e->getMessage());
        }
        return $result;
    }

}