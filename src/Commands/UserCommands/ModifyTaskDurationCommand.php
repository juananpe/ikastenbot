<?php

declare(strict_types=1);

// Longman's namespace must be used as otherwise the command is not recognized
namespace Longman\TelegramBot\Commands\UserCommands;

use IkastenBot\Entity\DatabaseBootstrap;
use IkastenBot\Entity\Task;
use IkastenBot\Exception\TaskNotFoundException;
use IkastenBot\Service\MessageSenderService;
use IkastenBot\Utils\MessageFormatterUtils;
use IkastenBot\Utils\TaskUtils;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Commands\UserCommand;

class ModifyTaskDurationCommand extends UserCommand
{
    /**
     * @inheritDoc
     */
    protected $name         = 'modifytaskduration';

    /**
     * @inheritDoc
     */
    protected $description  = 'Modify a task\'s duration in (+/-)X amount of days';

    /**
     * @inheritDoc
     */
    protected $usage        = '/modifytaskduration <taskID> <offset>';

    /**
     * @inheritDoc
     */
    protected $version      = '1.0.0';

    /**
     * @inheritDoc
     */
    protected $private_only = true;

    public function execute()
    {
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();
        $text    = trim($message->getText(true));

        $ms = new MessageSenderService();

        $remindUsageMessage = 'Command usage: ' . $this->getUsage();

        if ('' === $text) {
            $ms->prepareMessage($chat_id, $remindUsageMessage);
            return $ms->sendMessage();
        }

        if (!\preg_match('/^[0-9]+ (\+|-)?[0-9]+$/', $text)) {
            $ms->prepareMessage($chat_id, $remindUsageMessage . ' — Both arguments must be numbers.');
            return $ms->sendMessage();
        }

        $arguments = \explode(' ', $text);
        $taskId     = (int)$arguments[0];
        $taskOffset = (int)$arguments[1];

        $db = new DatabaseBootstrap();
        $em = $db->getEntityManager();

        $task = $em->getRepository(Task::class)->find($taskId);

        if(\is_null($task)) {
            $ms->prepareMessage($chat_id, $e->getMessage());
            return $ms->sendMessage('The specified task doesn\'t exist.');
        }

        $taskUtils = new TaskUtils($em);
        $taskUtils->modifyTaskDuration($task, $taskOffset);

        $ms->prepareMessage($chat_id, 'The task\'s duration was successfully modified.');
        return $ms->sendMessage();
    }
}
