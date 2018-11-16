<?php
/**
 * README
 * This file is intended to unset the webhook.
 * Uncommented parameters must be filled
 */

// Load composer
require_once __DIR__ . '/../../vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

if (!\array_key_exists('TBGP_ENV', $_SERVER)) {
    $dotenv = new Dotenv();
    $dotenv->load(__DIR__.'/../../.env');
}

// Add you bot's API key and name
$bot_api_key  = getenv('TELEGRAM_BOT_API_KEY');
$bot_username = getenv('TELEGRAM_BOT_USERNAME');

try {
    // Create Telegram API object
    $telegram = new Longman\TelegramBot\Telegram($bot_api_key, $bot_username);

    // Delete webhook
    $result = $telegram->deleteWebhook();

    if ($result->isOk()) {
        echo $result->getDescription();
    }
} catch (Longman\TelegramBot\Exception\TelegramException $e) {
    echo $e->getMessage();
}
