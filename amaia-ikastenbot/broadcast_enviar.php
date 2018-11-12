<?php

require __DIR__ . '/vendor/autoload.php';

use Longman\TelegramBot\DB;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Telegram;

require("config.php");


$telegram = new Telegram($bot_api_key,$bot_username);


$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$opt = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
$pdo = new PDO($dsn, $user, $pass, $opt);

$query = "SELECT id,user_id,message FROM notification where date=curdate() and sent=false;";
//$sth = $pdo->prepare($query);
//$sth->execute();
$sth= $pdo->query($query);
$notifs = $sth->fetchAll(\PDO::FETCH_ASSOC);



foreach($notifs as $ntf) {

    $data = [
        'chat_id' => $ntf['user_id'],
        'text'    => $ntf['message'],
    ];
echo $ntf['user_id'];
echo $ntf['message'];

    $result =Request::sendMessage($data);

    if ($result->isOk()) {
        $id = $ntf['id'];
        $query = "Update notification SET sent=true where id=$id;";
        $sth = $pdo->prepare($query);
        $sth->execute();
    }
}
