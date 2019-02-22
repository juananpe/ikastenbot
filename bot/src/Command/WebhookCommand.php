<?php

declare(strict_types=1);

namespace App\Command;

use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Telegram;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class WebhookCommand extends Command
{
    /**
     * Command's name.
     *
     * @var string
     */
    protected static $defaultName = 'app:webhook';

    protected function configure()
    {
        $this
            ->setDescription('Sets or unsets the Telegram webhook')
            ->setHelp('Sets or unsets the Telegram webhook using the environment variables\'s content')
            ->addOption(
                'set',
                null,
                InputOption::VALUE_NONE,
                'Set the webhook'
            )
            ->addOption(
                'unset',
                null,
                InputOption::VALUE_NONE,
                'Unset the webhook'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Setup Telegram object
        $telegram = new Telegram(getenv('TELEGRAM_BOT_API_KEY'), getenv('TELEGRAM_BOT_USERNAME'));

        // Get the options
        $set = $input->getOption('set');
        $unset = $input->getOption('unset');

        // Check that inputs are meaningful
        if ($set && $unset) {
            $output->writeln('Can\'t do both!');

            return;
        }

        if (!($set || $unset)) {
            $output->writeln('Nothing to do.');

            return;
        }

        // Set or unset the webhook
        try {
            $response = '';
            if ($set) {
                $response = $telegram->setWebhook(getenv('TELEGRAM_BOT_HOOK_URL').'webhook');
            }

            if ($unset) {
                $response = $telegram->deleteWebhook();
            }

            if ($response->isOk()) {
                $output->writeln($response->getDescription());
            }
        } catch (TelegramException $e) {
            $output->writeln($e->getMessage());
        }
    }
}
