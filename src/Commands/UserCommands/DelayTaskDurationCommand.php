<?php

declare(strict_types=1);

// Longman's namespace must be used as otherwise the command is not recognized

namespace Longman\TelegramBot\Commands\UserCommands;

use IkastenBot\Entity\DoctrineBootstrap;
use IkastenBot\Entity\Task;
use IkastenBot\Service\MessageSenderService;
use IkastenBot\Utils\FilesystemUtils;
use IkastenBot\Utils\MessageFormatterUtils;
use IkastenBot\Utils\XmlUtils;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Request;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;


class DelayTaskCommand extends UserCommand
{
    /**
     * {@inheritdoc}
     */
    protected $name = 'delaytask';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Delay a task for X days';

    /**
     * {@inheritdoc}
     */
    protected $usage = '/delaytask <taskID>';

    /**
     * {@inheritdoc}
     */
    protected $version = '1.0.0';

    /**
     * {@inheritdoc}
     */
    protected $private_only = true;

    public function execute()
    {
        $messageFormatterUtils = new MessageFormatterUtils();

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

            $text = \str_replace('/delaytask ', '', $data);

            $user = $callbackQuery->getFrom();
        }

        $chat = $message->getChat();
        $chat_id = $chat->getId();

        //reply to message id is applied by default
        //Force reply is applied by default so it can work with privacy on
        $selective_reply = $chat->isGroupChat() || $chat->isSuperGroup();

        // If it's a callback query, edit the message and remove the buttons
        if ($callbackQuery) {
            $data = [];

            // Edit the original message
            $data = [];
            $data['chat_id'] = $chat_id;
            $data['message_id'] = $message->getMessageId();

            $editedText = $message->getText();
            $editedText .= PHP_EOL.PHP_EOL;

            $messageFormatterUtils->appendTwigFile(
                $editedText,
                'notifications/task/delay/cheeryDelayMessage.twig'
            );

            $data['text'] = $editedText;

            Request::editMessageText($data);
        }

        $user_id = $user->getId();

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

        switch ($state) {
            case 0:
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

                // Store the task ID for the follow up
                $notes['taskId'] = $taskId;

                // Advance to the next state of the conversation
                $notes['state'] = $state + 1;

                // Store the notes in the database
                $this->conversation->update();

                // Ask the user for the delay of the task
                $parameters = [
                    'task' => $task,
                ];
                $responseMessage = '';
                $messageFormatterUtils->appendTwigFileWithParameters(
                    $responseMessage,
                    'notifications/task/delay/delayTask.twig',
                    $parameters
                );
                $ms->prepareMessage($chat_id, $responseMessage, 'HTML');

                return $ms->sendMessage();
            case 1:
                // If the supplied delay isn't a number, ask again
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
                $ganFilePath = DOWNLOAD_DIR.'/'.$ganttProject->getUser()->getId();
                $ganFilePath .= '/'.$ganttProject->getVersion();
                $ganFilePath .= '/'.$ganttProject->getFileName();

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
                $newGanFilePath = $fsUtils
                    ->saveToNewGanFile($newGanXml, $task->getGanttProject())
                ;

                // Prepare the success message to be sent and send it
                $parameters = [
                    'task' => $task,
                ];

                $responseMessage = '';
                $messageFormatterUtils->appendTwigFileWithParameters(
                    $responseMessage,
                    'notifications/task/delay/successDelayTask.twig',
                    $parameters
                );

                $ms->prepareMessage($chat_id, $responseMessage, 'HTML');
                $returnResult = $ms->sendMessage();

                Request::sendDocument([
                    'caption' => \sprintf(
                        'GanttProject version %s',
                        $task->getGanttProject()->getVersion() + 1
                    ),
                    'chat_id' => $chat_id,
                    'document' => Request::encodeFile($newGanFilePath),
                ]);

		$cmd = 'sudo -u juanan /usr/local/bin/docker exec gp xvfb-run -a /ganttproject-2.8.10-r2364/ganttproject -export png -o $FOLDER/$FILE.png $FOLDER/$FILE.gan';
		$process = \method_exists(Process::class, 'fromShellCommandline') ? Process::fromShellCommandline($cmd) : new Process($cmd);
		$process->run(null, [
			'FILE' => basename($newGanFilePath,".gan"),
			'FOLDER' => dirname($newGanFilePath)
		]);

		if (!$process->isSuccessful()){
                	$this->conversation->stop();
			throw new ProcessFailedException($process);
		}

		Request::sendDocument([
                    'chat_id' => $chat_id,
                    'document' => Request::encodeFile(dirname($newGanFilePath)."/".basename($newGanFilePath, ".gan") . ".png"),
                ]);

		/*
		$output =  $process->getOutput();
		$ms->prepareMessage($chat_id, $output);
		$ms->sendMessage();
		 */

                // Stop the conversation
                $this->conversation->stop();

                return $returnResult;
        }
    }
}
