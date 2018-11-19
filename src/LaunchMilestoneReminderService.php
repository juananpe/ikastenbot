<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Longman\TelegramBot\Telegram;
use Symfony\Component\Dotenv\Dotenv;
use TelegramBotGanttProject\Service\MilestoneReminderService;
use TelegramBotGanttProject\Service\MessageSenderService;
use TelegramBotGanttProject\Utils\MessageFormatterUtils;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

// Define project root
define('PROJECT_ROOT',  __DIR__ . '/..');

// Load environment variables
if (!\array_key_exists('TBGP_ENV', $_SERVER)) {
    $dotenv = new Dotenv();
    $dotenv->load(PROJECT_ROOT . '/.env');
}

//Setup database
$config = Setup::createAnnotationMetadataConfiguration(array(PROJECT_ROOT . "/src/Entity/"), false);

$connectionParams = array(
    'driver'   => 'pdo_mysql',
    'user'     => getenv('MYSQL_USERNAME'),
    'password' => getenv('MYSQL_USER_PASSWORD'),
    'dbname'   => getenv('MYSQL_DATABASE_NAME'),
);

$em = EntityManager::create($connectionParams, $config);

// Setup Twig
$loader = new FilesystemLoader(PROJECT_ROOT . '/templates/');
$twig = new Environment($loader, array(
    'cache' => PROJECT_ROOT . '/var/cache/',
));

// Setup message formatter utils
$mf = new MessageFormatterUtils($twig);

// Setup message sender service
$mss = new MessageSenderService();

// Setup Telegram object
$telegram = new Telegram(getenv('TELEGRAM_BOT_API_KEY'), getenv('TELEGRAM_BOT_USERNAME'));

// Notify users
$mrs = new MilestoneReminderService($em, $mf, $mss);
$mrs->notifyUsersMilestonesToday();
$mrs->notifyUsersMilestonesClose();
