<?php

declare(strict_types=1);

// Longman's namespace must be used as otherwise the command is not recognized
namespace Longman\TelegramBot\Commands\UserCommands;

use IkastenBot\Entity\DoctrineBootstrap;
use IkastenBot\Entity\Task;
use IkastenBot\Exception\TaskNotFoundException;
use IkastenBot\Service\MessageSenderService;
use IkastenBot\Utils\MessageFormatterUtils;
use IkastenBot\Utils\FilesystemUtils;
use IkastenBot\Utils\XmlUtils;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Commands\UserCommand;
use Symfony\Component\Filesystem\Filesystem;

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

        $db = new DoctrineBootstrap();
        $em = $db->getEntityManager();

        $task = $em->getRepository(Task::class)->find($taskId);

        /**
         * Check that the user who requested the modification is the owner of
         * the task
         */
        $taskOwner = $task->getGanttProject()->getUser()->getId();
        $isUserTheOwner = $taskOwner === $message->getFrom()->getId();

        /**
         * If the task doesn't exist or the user who requested the change isn't
         * the owner, return a task not found message. This is made on purpose
         * to avoid giving clues about other users' tasks to the user.
         */
        if(\is_null($task) || !$isUserTheOwner) {
            $ms->prepareMessage($chat_id, 'The specified task doesn\'t exist.');
            return $ms->sendMessage();
        }

        // Get the task's GanttProject
        $ganttProject = $task->getGanttProject();

        // Get the path of the Gan file
        $ganFilePath = DOWNLOAD_DIR . '/' . $ganttProject->getUser()->getId();
        $ganFilePath .= '/' . $ganttProject->getVersion();
        $ganFilePath .= '/' . $ganttProject->getFileName();

        // Delay the task and its dependants
        $xmlUtils = new XmlUtils($em);
        $newGanXml = $xmlUtils->delayTaskAndDependants(
                                                        $ganFilePath,
                                                        $task,
                                                        $taskOffset
                                                    );

        // Save the new Gan file
        $fs = new Filesystem();
        $fsUtils = new FilesystemUtils($em, $fs);
        $fsUtils->saveToNewGanFile($newGanXml, $task->getGanttProject());

        $ms->prepareMessage($chat_id,
            'The task\'s duration was successfully modified, and the ' .
            'GanttProject was updated.'
        );
        return $ms->sendMessage();
    }
}
