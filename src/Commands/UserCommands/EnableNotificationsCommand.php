<?php

declare(strict_types=1);

// Longman's namespace must be used as otherwise the command is not recognized
namespace Longman\TelegramBot\Commands\UserCommands;

use IkastenBot\Entity\DoctrineBootstrap;
use IkastenBot\Entity\Task;
use IkastenBot\Service\MessageSenderService;
use IkastenBot\Utils\MessageFormatterUtils;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Commands\UserCommand;

class EnableNotificationsCommand extends UserCommand
{
    /**
     * @inheritDoc
     */
    protected $name         = 'enablenotifications';

    /**
     * @inheritDoc
     */
    protected $description  = 'Enable the reminders for the specified task';

    /**
     * @inheritDoc
     */
    protected $usage        = '/enablenotifications <taskID>';

    /**
     * @inheritDoc
     */
    protected $version      = '1.0.0';

    /**
     * @inheritDoc
     */
    protected $need_mysql   = true;

    /**
     * @inheritDoc
     */
    protected $private_only = true;

    public function execute()
    {
        $chat       = $this->getMessage()->getChat();
        $chat_id    = $chat->getId();

        //reply to message id is applied by default
        //Force reply is applied by default so it can work with privacy on
        $selective_reply = $chat->isGroupChat() || $chat->isSuperGroup();

        $user       = $this->getMessage()->getFrom();
        $user_id    = $user->getId();

        $text       = trim($this->getMessage()->getText(true));

        $ms = new MessageSenderService();

        $remindUsageMessage = 'Command usage: ' . $this->getUsage();

        /**
         * If the command doesn't come with any parameters, remind the
         * user about the proper usage
         */
        if ('' === $text) {
            $ms->prepareMessage($chat_id, $remindUsageMessage);
            return $ms->sendMessage();
        }

        /**
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
         * owner of the task
         */
        $taskOwner = $task->getGanttProject()->getUser()->getId();
        $authorized = $taskOwner === $user_id;

        if (!$authorized) {
            $authorized = \preg_match(
                '/^' . getenv('TELEGRAM_BOT_USERNAME') . '$/mi', $user->getUsername()
            );
        }

        /**
         * If the task doesn't exist or the user who requested the
         * change isn't the owner, return a task not found message.
         * This is made on purpose to avoid giving clues about other
         * users' tasks to the user.
         */
        if(\is_null($task) || !$authorized) {
            $ms->prepareMessage($chat_id, 'The specified task ' .
                                           'doesn\'t exist.'
            );
            return $ms->sendMessage();
        }

        // Disable the notification for the task
        $task->setNotify(true);
        $em->persist($task);
        $em->flush();

        $ms->prepareMessage(
            $chat_id,
            'The notifications for the task have been enabled',
            null, $selective_reply);
        return $ms->sendMessage();
    }
}
