<?php

require __DIR__.'/../../vendor/autoload.php';

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Telegram;
use Symfony\Component\Dotenv\Dotenv;

if (!\array_key_exists('TBGP_ENV', $_SERVER)) {
    $dotenv = new Dotenv();
    $dotenv->load(__DIR__.'/../../.env');
}

$bot_api_key = getenv('TELEGRAM_BOT_API_KEY');
$bot_username = getenv('TELEGRAM_BOT_USERNAME');

$telegram = new Telegram($bot_api_key, $bot_username);

$charset = getenv('MYSQL_CHARSET');
$host = getenv('MYSQL_HOST');
$db = getenv('MYSQL_DATABASE_NAME');
$user = getenv('MYSQL_USERNAME');
$pass = getenv('MYSQL_USER_PASSWORD');

$dsn = "mysql:host=${host};dbname=${db};charset=${charset}";
$opt = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];
$pdo = new PDO($dsn, $user, $pass, $opt);

$query = 'SELECT id,user_id,message FROM notification where date=curdate() and sent=false;';
//$sth = $pdo->prepare($query);
//$sth->execute();
$sth = $pdo->query($query);
$notifs = $sth->fetchAll(\PDO::FETCH_ASSOC);

foreach ($notifs as $ntf) {
    $data = [
        'chat_id' => $ntf['user_id'],
        'text' => $ntf['message'],
    ];
    echo $ntf['user_id'];
    echo $ntf['message'];

    $result = Request::sendMessage($data);

    if ($result->isOk()) {
        $id = $ntf['id'];
        $query = "Update notification SET sent=true where id=${id};";
        $sth = $pdo->prepare($query);
        $sth->execute();
    }
}
