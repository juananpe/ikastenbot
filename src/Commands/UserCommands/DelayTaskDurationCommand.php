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

class DelayTaskCommand extends UserCommand
{
    /**
     * @inheritDoc
     */
    protected $name         = 'delaytask';

    /**
     * @inheritDoc
     */
    protected $description  = 'Delay a task for X days';

    /**
     * @inheritDoc
     */
    protected $usage        = '/delaytask <taskID>';

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
        $text    = trim($message->getText(true));

        $chat       = $this->getMessage()->getChat();
        $chat_id    = $chat->getId();

        //reply to message id is applied by default
        //Force reply is applied by default so it can work with privacy on
        $selective_reply = $chat->isGroupChat() || $chat->isSuperGroup();

        $user       = $this->getMessage()->getFrom();
        $user_id    = $user->getId();

        // Begin a new conversation
        $this->conversation = new Conversation($user_id, $chat_id, $this->getName());

        // Get the notes associated to this conversation
        $notes = &$this->conversation->notes;
        !is_array($notes) && $notes = [];

        //cache data from the tracking session if any
        $state = 0;
        if (isset($notes['state'])) {
            $state = $notes['state'];
        }

        $ms = new MessageSenderService();

        $db = new DoctrineBootstrap();
        $em = $db->getEntityManager();

        switch($state) {
            case 0:
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

                $task = $em->getRepository(Task::class)->find($taskId);

                /**
                 * Check that the user who requested the modification is the
                 * owner of the task
                 */
                $taskOwner = $task->getGanttProject()->getUser()->getId();
                $isUserTheOwner = $taskOwner === $user_id;

                /**
                 * If the task doesn't exist or the user who requested the
                 * change isn't the owner, return a task not found message.
                 * This is made on purpose to avoid giving clues about other
                 * users' tasks to the user.
                 */
                if(\is_null($task) || !$isUserTheOwner) {
                    $ms->prepareMessage($chat_id, 'The specified task ' .
                                                   'doesn\'t exist.'
                    );
                    return $ms->sendMessage();
                }

                // Store the task ID for the follow up
                $notes['taskId'] = $taskId;

                // Advance to the next state of the conversation
                $notes['state'] = $state + 1;

                // Store the notes in the database
                $this->conversation->update();

                // Ask the user for the delay of the task
                $ms->prepareMessage($chat_id, 'Please specify in days the ' .
                                                'delay of the task'
                );
                return $ms->sendMessage();

            case 1:
                /**
                 * If the supplied delay isn't a number, ask again
                 */
                if (!\preg_match('/^[0-9]+$/', $text)) {
                    $ms->prepareMessage($chat_id, 'Please send a positive number');
                    return $ms->sendMessage();
                }

                // Fetch the task ID and the task from the database
                $taskId = $notes['taskId'];

                $task = $em->getRepository(Task::class)->find($taskId);

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
                                                                (int) $text
                                                            );

                // Save the new Gan file
                $fs = new Filesystem();
                $fsUtils = new FilesystemUtils($em, $fs);
                $fsUtils->saveToNewGanFile($newGanXml, $task->getGanttProject());

                $ms->prepareMessage($chat_id,
                    'The task was successfully delayed, and the ' .
                    'GanttProject was updated.'
                );

                // Stop the conversation
                $this->conversation->stop();

                return $ms->sendMessage();
        }
    }
}
