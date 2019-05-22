<?php

declare(strict_types=1);

// Longman's namespace must be used as otherwise the command is not recognized

namespace Longman\TelegramBot\Commands\UserCommands;

use App\Entity\DoctrineBootstrap;
use App\Entity\GanttProject;
use App\Entity\User;
use App\Exception\IncorrectFileException;
use App\Exception\NoTasksException;
use App\Service\MessageFormatterUtilsService;
use App\Service\MessageSenderService;
use App\Service\NotificationManagerService;
use App\Service\SimilarTasksDurationNotifier;
use App\Service\XmlUtilsService;
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
        $db = DoctrineBootstrap::instance();
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
        $xmlManCon = new XmlUtilsService($em);

        try {
            $tasks = $xmlManCon->extractStoreTasks($ganFilePath, $chat->getId(), $gt);

            // send message related to similar tasks' average durations
            $notifier = new SimilarTasksDurationNotifier($chat_id);
            $notifier->NotifyOfAtypicalTasks($tasks);
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

        // Disable notifications for tasks related to previous versions of the
        // GanttProject
        $notificationManagerService = new NotificationManagerService($em);
        $notificationManagerService->disableNotificationsForOutdatedTasks($chat_id, $gt);

        $this->conversation->stop();
        $ms->prepareMessage($chat_id, $this->prepareFormattedMessage($tasks), 'HTML', $selective_reply);
        $result = $ms->sendMessage();

        // Check if the tasks contain milestones
        if (!$this->hasMilestones($tasks)) {
            // TODO añadir a la base de datos el mensaje
            //$text = $db->getSystemMessageById(?, $user->getLanguageCode());

            // If no milestones were found, send a warning message.
            $text = 'ATENCIÓN:';
            $text .= PHP_EOL.'No he detectado ningún hito (milestone) en tu diagrama Gantt.';
            $text .= PHP_EOL.'Es muy recomendable que tu diagrama tenga hitos para ayudar con el seguimiento.';
            $ms->prepareMessage($chat_id, $text, null, $selective_reply);
            $ms->sendMessage();
        }

        // Check if the tasks contain tracking meetings
        if (!$this->hasTrackingMeetings($tasks)) {
            // TODO añadir a la base de datos el mensaje
            //$text = $db->getSystemMessageById(?, $user->getLanguageCode());

            // If no meetings were found, send a warning message.
            $text = 'ATENCIÓN:';
            $text .= PHP_EOL.'No he detectado ninguna tarea o hito haciendo referencia a reuniones de seguimiento.';
            $text .= PHP_EOL.'Deberías añadir reuniones regulares con el cliente/tutor del proyecto.';
            $ms->prepareMessage($chat_id, $text, null, $selective_reply);
            $ms->sendMessage();
        }

        return $result;
    }

    /**
     * Detects whether a list of tasks contains a milestone.
     *
     * @param array $tasks the list of tasks
     *
     * @return bool True if at least one of the tasks is a milestone. False otherwise.
     */
    private function hasMilestones(array $tasks): bool
    {
        foreach ($tasks as $task) {
            if ($task->getIsMilestone()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detects whether a list of tasks contains task related to tracking meetings.
     * For this, a number of key words are searched within the tasks's names.
     *
     * @param array $tasks the list of tasks
     *
     * @return bool True if at least one of the tasks is related to tracking meetings. False otherwise.
     */
    private function hasTrackingMeetings(array $tasks): bool
    {
        $keywords = ['meeting', 'reunión', 'reunion', 'tracking', 'seguimiento'];
        foreach ($tasks as $task) {
            foreach ($keywords as $string) {
                // look for tasks containing any of the keywords
                // that are milestones or have duration 1
                if (false !== strpos(strtolower($task->getName()), $string)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Prepare a formatted message with the tasks to be reminded of.
     *
     * @param array $tasks Array of Task objects
     *
     * @return string Formatted message in HTML
     */
    private function prepareFormattedMessage(array $tasks): string
    {
        $mf = new MessageFormatterUtilsService();

        $text = 'You will be reminded of the following tasks:';
        $text .= PHP_EOL.PHP_EOL;

        foreach ($tasks as $task) {
            if ($task->getNotify()) {
                $mf->appendTask($text, $task, null, $task->getIsMilestone());
            }
        }

        return $text;
    }
}
