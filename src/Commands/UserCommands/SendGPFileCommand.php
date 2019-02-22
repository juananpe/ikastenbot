<?php

declare(strict_types=1);

// Longman's namespace must be used as otherwise the command is not recognized

namespace Longman\TelegramBot\Commands\UserCommands;

use IkastenBot\Entity\DoctrineBootstrap;
use IkastenBot\Entity\GanttProject;
use IkastenBot\Entity\User;
use IkastenBot\Exception\IncorrectFileException;
use IkastenBot\Exception\NoTasksException;
use IkastenBot\Service\MessageSenderService;
use IkastenBot\Utils\MessageFormatterUtils;
use IkastenBot\Utils\XmlUtils;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Request;
use Symfony\Component\Filesystem\Filesystem;

class SendGpFileCommand extends UserCommand
{
    /**
     * {@inheritdoc}
     */
    protected $name = 'sendgpfile';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Send the GP file to the bot';

    /**
     * {@inheritdoc}
     */
    protected $usage = '/sendgpfile';

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
    protected $conversation;

    /**
     * {@inheritdoc}
     */
    protected $private_only = true;

    public function execute()
    {
        $chat = $this->getMessage()->getChat();
        $chat_id = $chat->getId();

        //reply to message id is applied by default
        //Force reply is applied by default so it can work with privacy on
        $selective_reply = $chat->isGroupChat() || $chat->isSuperGroup();

        $user = $this->getMessage()->getFrom();
        $user_id = $user->getId();

        $this->conversation = new Conversation($user_id, $chat_id, $this->getName());

        $ms = new MessageSenderService();

        // Get the sent document
        $document = $this->getMessage()->getDocument();
        if (null === $document) {
            $this->conversation->update();
            $ms->prepareMessage($chat_id, 'Please send your GanttProject\'s XML file.', null, $selective_reply);

            return $ms->sendMessage();
        }

        // Fetch the user from the database and the corresponding GanttProject
        $db = new DoctrineBootstrap();
        $em = $db->getEntityManager();
        $user = $em->getRepository(User::class)->find($user_id);
        $ganttProject = $em->getRepository(GanttProject::class)->findLatestGanttProject($user);
        $ganttProjectVersion = 0;

        // Create a new directory for each version of the sent file
        $defaultDownloadPath = $this->telegram->getDownloadPath();
        $specificDownloadPath = $defaultDownloadPath.'/'.$user_id;
        if (\is_null($ganttProject)) {
            $specificDownloadPath .= '/1';
            $ganttProjectVersion = 1;
        } else {
            $ganttProjectVersion = $ganttProject->getVersion() + 1;
            $specificDownloadPath .= '/'.$ganttProjectVersion;
        }

        $filesystem = new Filesystem();
	$filesystem->mkdir($specificDownloadPath);
	// FIXME
        $filesystem->chmod($specificDownloadPath, 0777);

        // Set the library's download path to the generated path temporarily
        $this->telegram->setDownloadPath($specificDownloadPath);

        // Download the file
        $response = Request::getFile(['file_id' => $document->getFileId()]);
        if (!Request::downloadFile($response->getResult())) {
            $ms->prepareMessage($chat_id, 'There was an error obtaining your file. Please send it again.', null, $selective_reply);

            return $ms->sendMessage();
        }

        // Set back the library's download path to the default one
        $this->telegram->setDownloadPath($defaultDownloadPath);

        // Rename the file to the original name it had and delete the
        // 'documents' folder
        $ganFilePath = $specificDownloadPath.'/'.$document->getFileName();
        $filesystem->rename(
            $specificDownloadPath.'/'.$response->getResult()->getFilePath(),
            $ganFilePath
        );
        $filesystem->remove($specificDownloadPath.'/documents');

        // Create a new GanttProject
        $gt = new GanttProject();
        $gt->setFileName($document->getFileName());
        $gt->setVersion($ganttProjectVersion);

        $user->addGanttProject($gt);
        $em->persist($user);
        $em->flush();

        // Extract the tasks and store them in the database
        $xmlManCon = new XmlUtils($em);

        try {
            $tasks = $xmlManCon->extractStoreTasks($ganFilePath, $chat->getId(), $gt);
        } catch (NoTasksException $e) {
            $ms->prepareMessage($chat_id, $e->getMessage(), null, $selective_reply);

            return $ms->sendMessage();
        } catch (IncorrectFileException $e) {
            $ms->prepareMessage(
                $chat_id,
                'The provided Gan file could not be processed. Please send a '.
                'valid Gan file.',
                null,
                $selective_reply
            );

            return $ms->sendMessage();
        }
        $this->conversation->stop();
        $ms->prepareMessage($chat_id, $this->prepareFormattedMessage($tasks), 'HTML', $selective_reply);

        return $ms->sendMessage();
    }

    /**
     * Prepare a formatted message with the tasks to be reminded of.
     *
     * @param IkastenBot\Entity\Task[] $tasks Array of Task objects
     *
     * @return string Formatted message in HTML
     */
    private function prepareFormattedMessage(array $tasks): string
    {
        $mf = new MessageFormatterUtils();

        $text = 'You will be reminded of the following tasks:';
        $text .= PHP_EOL.PHP_EOL;

        foreach ($tasks as $task) {
            $mf->appendTask($text, $task, null, $task->getIsMilestone());
        }

        return $text;
    }
}
