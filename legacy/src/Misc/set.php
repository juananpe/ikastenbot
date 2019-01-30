<?php
/**
 * README
 * This file is intended to set the webhook.
 * Uncommented parameters must be filled.
 */

// Load composer
require_once __DIR__.'/../../vendor/autoload.php';

use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Telegram;
use Symfony\Component\Dotenv\Dotenv;

if (!\array_key_exists('TBGP_ENV', $_SERVER)) {
    $dotenv = new Dotenv();
    $dotenv->load(__DIR__.'/../../.env');
}

// Add you bot's API key and name
$bot_api_key = getenv('TELEGRAM_BOT_API_KEY');
$bot_username = getenv('TELEGRAM_BOT_USERNAME');

// Define the URL to your hook.php file
$hook_url = getenv('TELEGRAM_BOT_HOOK_URL');

try {
    // Create Telegram API object
    $telegram = new Telegram($bot_api_key, $bot_username);

    // Set webhook
    $result = $telegram->setWebhook($hook_url);

    // To use a self-signed certificate, use this line instead
    //$result = $telegram->setWebhook($hook_url, ['certificate' => $certificate_path]);

    if ($result->isOk()) {
        echo $result->getDescription();
    }
} catch (TelegramException $e) {
    echo $e->getMessage();
}
