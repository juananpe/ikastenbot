<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

use IkastenBot\Entity\DoctrineBootstrap;
use IkastenBot\Service\MessageSenderService;
use IkastenBot\Service\TaskReminderService;
use IkastenBot\Utils\MessageFormatterUtils;
use Longman\TelegramBot\Telegram;
use Symfony\Component\Dotenv\Dotenv;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

// Define project root
define('PROJECT_ROOT', __DIR__.'/..');

// Load environment variables
if (!\array_key_exists('TBGP_ENV', $_SERVER)) {
    $dotenv = new Dotenv();
    $dotenv->load(PROJECT_ROOT.'/.env');
}

// Setup database
$db = new DoctrineBootstrap();
$em = $db->getEntityManager();

// Setup Twig
$loader = new FilesystemLoader(PROJECT_ROOT.'/templates/');
$twig = new Environment($loader, [
    'cache' => PROJECT_ROOT.'/var/cache/Twig',
]);

// Setup message formatter utils
$mf = new MessageFormatterUtils($twig);

// Setup message sender service
$mss = new MessageSenderService();

// Setup Telegram object
$telegram = new Telegram(getenv('TELEGRAM_BOT_API_KEY'), getenv('TELEGRAM_BOT_USERNAME'));

// Notify users
$trs = new TaskReminderService($em, $mf, $mss);
$trs->notifyUsersMilestonesToday();
$trs->notifyUsersMilestonesClose();
