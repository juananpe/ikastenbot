<?php

require __DIR__ . '/../../vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

function insertDates($text, $dates){

//        $texto = str_replace("##", $dates, $text);
$texto = $text . " " . $dates;
    return $texto;
}

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
$pdo = new PDO($dsn, $user, $pass, $opt);

$query = "select id,`to`,what from dates where `to`=CURDATE() + INTERVAL 7 DAY and notification_prep=FALSE ";
$sth = $pdo->prepare($query);
$sth->execute();
$fecha = $sth->fetchAll(\PDO::FETCH_ASSOC);

$query = "select id,`language` from `user`";
$sth = $pdo->prepare($query);
$sth->execute();
$users= $sth->fetchAll(\PDO::FETCH_ASSOC);

foreach($fecha as $convo){
    switch ($convo['what']){
        case "matricula":
            $id_msg=2;
            break;
        case "secretaria":
            $id_msg=3;
            break;
        case "solicitud defensa":
            $id_msg=4;
            break;
        case "visto bueno":
            $id_msg=5;
            break;
        case "defensa":
            $id_msg=6;
            break;
    }
    $query = "select es,eus from system_message where id=$id_msg";
    $sth = $pdo->prepare($query);
    $sth->execute();
    $msg= $sth->fetch(\PDO::FETCH_ASSOC);

    $msg_date['es'] = insertDates($msg['es'],$convo['to']);
    $msg_date['eus'] = insertDates($msg['eus'],$convo['to']);

    foreach ($users as $user){
        $user_id  = $user['id'];
        $lang = $user['language'];
        if(array_key_exists($lang,$msg_date)){

            $user_msg = $msg_date[$lang];
            $query = "INSERT INTO notification (user_id,`date`,message,sent) VALUES ($user_id,CURDATE(),'$user_msg',0); ";
            $sth = $pdo->prepare($query);
            $sth->execute();
        }
    }

    $id = $convo['id'];
    $query = "Update `dates` SET notification_prep=true where id=$id";
    $sth = $pdo->prepare($query);
    $sth->execute();

}