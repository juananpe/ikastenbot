<?php
/**
 * README
 * This configuration file is intended to run the bot with the webhook method.
 * Uncommented parameters must be filled
 *
 * Please note that if you open this file with your browser you'll get the "Input is empty!" Exception.
 * This is a normal behaviour because this address has to be reached only by the Telegram servers.
 */

// Load composer
require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

define('PROJECT_ROOT',  __DIR__ . '/..');

$dotenv = new Dotenv();
$dotenv->load(PROJECT_ROOT . '/.env');

// Add you bot's API key and name
$bot_api_key  = getenv('TELEGRAM_BOT_API_KEY');
$bot_username = getenv('TELEGRAM_BOT_USERNAME');

// Define all IDs of admin users in this array (leave as empty array if not used)
$admin_users = [
//    123,
];

// Define all paths for your custom commands in this array (leave as empty array if not used)
$commands_paths = [
    PROJECT_ROOT . '/src/Commands/',
];

$mysql_host             = getenv('MYSQL_HOST');
$mysql_database         = getenv('MYSQL_DATABASE_NAME');
$mysql_user             = getenv('MYSQL_USERNAME');
$mysql_user_password    = getenv('MYSQL_USER_PASSWORD');

$isMysqlEnabled = !(empty($mysql_host) || empty($mysql_database) || empty($mysql_user) || empty($mysql_user_password));

if($isMysqlEnabled) {
    $mysql_credentials = [
        'host'     => $mysql_host,
        'user'     => $mysql_user,
        'password' => $mysql_user_password,
        'database' => $mysql_database,
    ];
}

unset($mysql_host);
unset($mysql_database);
unset($mysql_user);
unset($mysql_user_password);


try {
    // Create Telegram API object
    $telegram = new Longman\TelegramBot\Telegram($bot_api_key, $bot_username);

    // Add commands paths containing your custom commands
    $telegram->addCommandsPaths($commands_paths);

    // Enable admin users
    $telegram->enableAdmins($admin_users);

    // Enable MySQL
    if ($isMysqlEnabled) {
        $telegram->enableMySql($mysql_credentials);
    }
    // Logging (Error, Debug and Raw Updates)
    //Longman\TelegramBot\TelegramLog::initErrorLog(__DIR__ . "/{$bot_username}_error.log");
    //Longman\TelegramBot\TelegramLog::initDebugLog(__DIR__ . "/{$bot_username}_debug.log");
    //Longman\TelegramBot\TelegramLog::initUpdateLog(__DIR__ . "/{$bot_username}_update.log");

    // If you are using a custom Monolog instance for logging, use this instead of the above
    //Longman\TelegramBot\TelegramLog::initialize($your_external_monolog_instance);

    // Set custom Upload and Download paths
    $downloadDirectory = getenv('TELEGRAM_DOWNLOAD_DIRECTORY');
    $uploadDirectory   = getenv('TELEGRAM_UPLOAD_DIRECTORY');

    if (!(empty($downloadDirectory) || empty($uploadDirectory))) {
        $telegram->setDownloadPath(PROJECT_ROOT . getenv('TELEGRAM_DOWNLOAD_DIRECTORY'));
        $telegram->setUploadPath(PROJECT_ROOT . getenv('TELEGRAM_UPLOAD_DIRECTORY'));
    }

    unset($downloadDirectory);
    unset($uploadDirectory);

    // Here you can set some command specific parameters
    // e.g. Google geocode/timezone api key for /date command
    //$telegram->setCommandConfig('date', ['google_api_key' => 'your_google_api_key_here']);

    // Botan.io integration
    //$telegram->enableBotan('your_botan_token');

    // Requests Limiter (tries to prevent reaching Telegram API limits)
    $telegram->enableLimiter();

    // Handle telegram webhook request
    $telegram->handle();

} catch (Longman\TelegramBot\Exception\TelegramException $e) {
    // Silence is golden!
    //echo $e;
    // Log telegram errors
    Longman\TelegramBot\TelegramLog::error($e);
} catch (Longman\TelegramBot\Exception\TelegramLogException $e) {
    // Silence is golden!
    // Uncomment this to catch log initialisation errors
    //echo $e;
}
