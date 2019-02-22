<?php

declare(strict_types=1);

/*
 * This file lets php-telegram-bot handle the request. Instead of embedding
 * all this code into Symfony's index.php, this file is kept aside for the sake
 * of cleanness.
 */

use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\TelegramLog;

define('PROJECT_ROOT', __DIR__.'/..');

$mysqlHost = getenv('MYSQL_HOST');
$mysqlDatabase = getenv('MYSQL_DATABASE_NAME');
$mysqlUser = getenv('MYSQL_USERNAME');
$mysqlUserPassword = getenv('MYSQL_USER_PASSWORD');

$isMysqlEnabled = !(empty($mysqlHost) || empty($mysqlDatabase) || empty($mysqlUser) || empty($mysqlUserPassword));

if ($isMysqlEnabled) {
    $mysqlCredentials = [
        'host' => $mysqlHost,
        'user' => $mysqlUser,
        'password' => $mysqlUserPassword,
        'database' => $mysqlDatabase,
    ];
}

unset($mysqlHost, $mysqlDatabase, $mysqlUser, $mysqlUserPassword);

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

    // Enable MySQL
    if ($isMysqlEnabled) {
        $telegram->enableMySql($mysqlCredentials);
    }

    // Set custom Upload and Download paths
    $downloadDirectory = getenv('TELEGRAM_DOWNLOAD_DIRECTORY');
    $uploadDirectory = getenv('TELEGRAM_UPLOAD_DIRECTORY');

    if (!(empty($downloadDirectory) || empty($uploadDirectory))) {
        if ('files/download' === $downloadDirectory) {
            $downloadDirectory = PROJECT_ROOT.'/'.$downloadDirectory;
        }

        if ('files/upload' === $uploadDirectory) {
            $uploadDirectory = PROJECT_ROOT.'/'.$uploadDirectory;
        }

        define('DOWNLOAD_DIR', $downloadDirectory);
        define('UPLOAD_DIR', $uploadDirectory);

        $telegram->setDownloadPath($downloadDirectory);
        $telegram->setUploadPath($uploadDirectory);
    }

    unset($downloadDirectory, $uploadDirectory);

    // Requests Limiter (tries to prevent reaching Telegram API limits)
    $telegram->enableLimiter();

    // Handle telegram webhook request
    $telegram->handle();
} catch (TelegramException $e) {
    TelegramLog::error($e);
}
