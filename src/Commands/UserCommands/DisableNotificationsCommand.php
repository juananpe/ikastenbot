<?php

declare(strict_types=1);

// Longman's namespace must be used as otherwise the command is not recognized

namespace Longman\TelegramBot\Commands\UserCommands;

use IkastenBot\Entity\DoctrineBootstrap;
use IkastenBot\Entity\Task;
use IkastenBot\Service\MessageSenderService;
use IkastenBot\Utils\MessageFormatterUtils;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;

class DisableNotificationsCommand extends UserCommand
{
    /**
     * {@inheritdoc}
     */
    protected $name = 'disablenotifications';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Disable the reminders for the specified task';

    /**
     * {@inheritdoc}
     */
    protected $usage = '/disablenotifications <taskID>';

    /**
     * {@inheritdoc}
     */
    protected $version = '1.0.0';

    /**
     * {@inheritdoc}
     */
    protected $need_mysql = true;

    /**
     * {@inheritdoc}
     */
    protected $private_only = true;

    public function execute()
    {
        $mfu = new MessageFormatterUtils();

        $message = $this->getMessage();
        $callbackQuery = $this->getUpdate()->getCallbackQuery();

        // If it's a callback query extract the information from there
        $text = '';
        if (!\is_null($message)) {
            $text = trim($message->getText(true));
            $user = $message->getFrom();
        } else {
            $message = $callbackQuery->getMessage();
            $data = $callbackQuery->getData();

            $text = \str_replace('/disablenotifications ', '', $data);

            $user = $callbackQuery->getFrom();
        }

        $chat = $message->getChat();
        $chat_id = $chat->getId();

        /*
         * If it's a callback query, edit the original message and remove the
         * buttons from the chat
         */
        if ($callbackQuery) {
            // Edit the original message
            $data = [];
            $data['chat_id'] = $chat_id;
            $data['message_id'] = $message->getMessageId();

            $editedText = $message->getText();
            $editedText .= PHP_EOL.PHP_EOL;

            $mfu->appendTwigFile(
                $editedText,
                'notifications/notFurtherNotifications.twig'
            );

            $data['text'] = $editedText;

            Request::editMessageText($data);

            // Remove the buttons from the chat
            $data = [];
            $data['chat_id'] = $chat_id;
            $data['message_id'] = $message->getMessageId();
            Request::editMessageReplyMarkup($data);
        }

        $user_id = $user->getId();

        $ms = new MessageSenderService();

        $remindUsageMessage = 'Command usage: '.$this->getUsage();

        /*
         * If the command doesn't come with any parameters, remind the
         * user about the proper usage
         */
        if ('' === $text) {
            $ms->prepareMessage($chat_id, $remindUsageMessage);

            return $ms->sendMessage();
        }

        /*
         * If the command isn't supplied with a task id, remind the
         * user about the proper usage
         */
        if (!\preg_match('/^[0-9]+$/', $text)) {
            $ms->prepareMessage($chat_id, $remindUsageMessage);

            return $ms->sendMessage();
        }

        // Fetch the task from the database
        $taskId = $text;

        $db = new DoctrineBootstrap();
        $em = $db->getEntityManager();
        $task = $em->getRepository(Task::class)->find($taskId);

        /**
         * Check that the user who requested the modification is the
         * owner of the task.
         */
        $taskOwner = $task->getGanttProject()->getUser()->getId();
        $authorized = $taskOwner == $user_id;

        if (!$authorized) {
            $authorized = \preg_match(
                '/^'.getenv('TELEGRAM_BOT_USERNAME').'$/mi',
                $user->getUsername()
            );
        }

        /*
         * If the task doesn't exist or the user who requested the
         * change isn't the owner, return a task not found message.
         * This is made on purpose to avoid giving clues about other
         * users' tasks to the user.
         */
        if (\is_null($task) || !$authorized) {
            $ms->prepareMessage(
                $chat_id,
                'The specified task doesn\'t exist.'
            );

            return $ms->sendMessage();
        }

        // Disable the notification for the task
        $task->setNotify(false);
        $em->persist($task);
        $em->flush();

        /**
         * Send a message only if the user manually modified the notifications
         * for a task.
         */
        $text = '';
        if (!$callbackQuery) {
            $mfu->appendTwigFile(
                $text,
                'notifications/notFurtherNotifications.twig'
            );
            $ms->prepareMessage($chat_id, $text);

            return $ms->sendMessage();
        }
    }
}
