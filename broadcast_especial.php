<?php

require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/.env');

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

$query = "select id,es,eus,`date` from special_notification where `date`<=CURDATE() + INTERVAL 3 DAY and notification_prep=FALSE ";
$sth = $pdo->prepare($query);
$sth->execute();
$nots = $sth->fetchAll(\PDO::FETCH_ASSOC);

$query = "select id,`language` from `user`";
$sth = $pdo->prepare($query);
$sth->execute();
$users= $sth->fetchAll(\PDO::FETCH_ASSOC);


foreach($nots as $not){

    $msg_date['es'] = $not['es'];
    $msg_date['eus'] =$not['eus'];

    foreach ($users as $user){
        $user_id  = $user['id'];
        $lang = $user['language'];
        $date = $not['date'];
        if(array_key_exists($lang,$msg_date)){

            $user_msg = $msg_date[$lang];
            $query = "INSERT INTO notification (user_id,`date`,message,sent) VALUES ($user_id,'$date','$user_msg',0); ";
            $sth = $pdo->prepare($query);
            $sth->execute();
        }
    }
    $id = $not['id'];
    $query = "Update special_notification SET notification_prep=true where id=$id;";
    $sth = $pdo->prepare($query);
    $sth->execute();

}