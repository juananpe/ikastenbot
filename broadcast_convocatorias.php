<?php

require __DIR__ . '/vendor/autoload.php';



function insertDates($text, $dates){

        $texto = str_replace("##", $dates, $text);

    return $texto;
}


$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$opt = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
$pdo = new PDO($dsn, $user, $pass, $opt);

$query = "select `to`,what from dates where `to`=CURDATE() + INTERVAL 7 DAY and notification_prep=FALSE ";
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
            $id_msg=18;
            break;
        case "secretaria":
            $id_msg=19;
            break;
        case "solicitud defensa":
            $id_msg=20;
            break;
        case "visto bueno":
            $id_msg=21;
            break;
        case "defensa":
            $id_msg=22;
            break;
    }
    $query = "select es,eus from messages_lang where id=$id_msg";
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

    $id = $not['id'];
    $query = "Update `dates` SET notification_prep=true where id=$id;";
    $sth = $pdo->prepare($query);
    $sth->execute();

}