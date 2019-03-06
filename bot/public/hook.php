<?php

declare(strict_types=1);

/*
 * This file lets php-telegram-bot handle the request. Instead of embedding
 * all this code into Symfony's index.php, this file is kept aside for the sake
 * of cleanness.
 */

use App\Entity\DoctrineBootstrap;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\TelegramLog;

define('PROJECT_ROOT', __DIR__.'/..');

try {
    // Create Telegram API object
    $telegram = new Telegram(
        getenv('TELEGRAM_BOT_API_KEY'),
        getenv('TELEGRAM_BOT_USERNAME')
    );

    // Bot's command paths
    $telegram->addCommandsPaths([
        PROJECT_ROOT.'/botcommands/',
    ]);

    // Enable MySQL for the bot.
    $doctrineBootstrap = DoctrineBootstrap::instance();
    $entityManager = $doctrineBootstrap->getEntityManager();

    $telegram->enableMySql($entityManager->getConnection()->getWrappedConnection());

    // Set download and upload paths
    $downloadDirectory = getenv('TELEGRAM_DOWNLOAD_DIRECTORY');
    $uploadDirectory = getenv('TELEGRAM_UPLOAD_DIRECTORY');

    if (empty($downloadDirectory)) {
        $downloadDirectory = PROJECT_ROOT.'/var/ganfiles/download';
    }
    define('DOWNLOAD_DIR', $downloadDirectory);
    $telegram->setDownloadPath($downloadDirectory);

    if (empty($uploadDirectory)) {
        $uploadDirectory = PROJECT_ROOT.'/var/ganfiles/upload';
    }
    define('UPLOAD_DIR', $uploadDirectory);
    $telegram->setUploadPath($uploadDirectory);

    unset($downloadDirectory, $uploadDirectory);

    // Requests Limiter (tries to prevent reaching Telegram API limits)
    $telegram->enableLimiter();

    // Handle telegram webhook request
    $telegram->handle();
} catch (TelegramException $e) {
    TelegramLog::error($e);
}
