<?php

declare(strict_types=1);

// Load composer
require_once __DIR__ . '/../vendor/autoload.php';

use IkastenBot\Entity\DoctrineBootstrap;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Symfony\Component\Dotenv\Dotenv;

define('PROJECT_ROOT',  __DIR__ . '/..');

$dotenv = new Dotenv();
$dotenv->load(PROJECT_ROOT . '/.env');

$db = new DoctrineBootstrap();

// replace with mechanism to retrieve EntityManager in your app
$entityManager = $db->getEntityManager();

return ConsoleRunner::createHelperSet($entityManager);