<?php

declare(strict_types=1);

// Load composer
require_once __DIR__.'/../vendor/autoload.php';

use Doctrine\ORM\Tools\Console\ConsoleRunner;
use IkastenBot\Entity\DoctrineBootstrap;
use Symfony\Component\Dotenv\Dotenv;

define('PROJECT_ROOT', __DIR__.'/..');

$dotenv = new Dotenv();
$dotenv->load(PROJECT_ROOT.'/.env');

$db = new DoctrineBootstrap();

// replace with mechanism to retrieve EntityManager in your app
$entityManager = $db->getEntityManager();

// @see https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/cookbook/mysql-enums.html
$entityManager
    ->getConnection()
    ->getDatabasePlatform()
    ->registerDoctrineTypeMapping('enum', 'string')
;

return ConsoleRunner::createHelperSet($entityManager);
