<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use MikelAlejoBR\TelegramBotGanttProject\Service\MilestoneReminderService;
use MikelAlejoBR\TelegramBotGanttProject\Service\MessageSenderService;
use Symfony\Component\Dotenv\Dotenv;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

// Load environment variables
$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/../.env');

//Setup database
$config = Setup::createAnnotationMetadataConfiguration(array(__DIR__."/../Entity/"), false);

$connectionParams = array(
    'driver'   => 'pdo_mysql',
    'user'     => getenv('MYSQL_USERNAME'),
    'password' => getenv('MYSQL_USER_PASSWORD'),
    'dbname'   => getenv('MYSQL_DATABASE_NAME'),
);

$em = EntityManager::create($connectionParams, $config);

// Setup Twig
$loader = new FilesystemLoader(__DIR__ . '/../templates/');
$twig = new Environment($loader, array(
    'cache' => __DIR__ . '/../var/cache/',
));

// Setup message sender service
$mss = new MessageSenderService();

// Notify users
$mrs = new MilestoneReminderService($em, $mss, $twig);
$mrs->notifyUsers();
