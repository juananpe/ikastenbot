<?php

declare(strict_types=1);

// Longman's namespace must be used as otherwise the command is not recognized
namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\Update;
use TelegramBotGanttProject\Exception\IncorrectFileException;
use TelegramBotGanttProject\Exception\NoMilestonesException;
use TelegramBotGanttProject\Service\MessageSenderService;
use TelegramBotGanttProject\Utils\XmlUtils;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class SendGpFileCommand extends UserCommand
{
    /**
     * @inheritDoc
     */
    protected $name         = 'SendGpFile';

    /**
     * @inheritDoc
     */
    protected $description  = 'Send the GP file to the bot';

    /**
     * @inheritDoc
     */
    protected $usage        = '/sendgpfile';

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
    protected $conversation;

    /**
     * @inheritDoc
     */
    protected $private_only = true;

    /**
     * Twig templating engine
     *
     * @var Environment
     */
    protected $twig;

    /**
     * Prepare a formatted message with the milestones to be reminded of
     *
     * @param   array   $milestones Array of Milestone objects
     * @return  string              Formatted message in HTML
     */
    private function prepareFormattedMessage(array $milestones): string
    {
        return $this->twig->render('notifications/remindedOfNotification.txt.twig', [
            'milestones' => $milestones
        ]);
    }

    public function execute()
    {
        $chat       = $this->getMessage()->getChat();
        $chat_id    = $chat->getId();

        //reply to message id is applied by default
        //Force reply is applied by default so it can work with privacy on
        $selective_reply = $chat->isGroupChat() || $chat->isSuperGroup();

        $user       = $this->getMessage()->getFrom();
        $user_id    = $user->getId();

        $this->conversation = new Conversation($user_id, $chat_id, $this->getName());

        $loader = new FilesystemLoader(PROJECT_ROOT . '/templates/');
        $this->twig = new Environment($loader, array(
            'cache' => PROJECT_ROOT . '/var/cache/',
        ));

        $ms = new MessageSenderService();

        // Get the sent document
        $document = $this->getMessage()->getDocument();
        if (null === $document) {
            $this->conversation->update();
            $ms->prepareMessage($chat_id, 'Please send your GanttProject\'s XML file.', null, $selective_reply);
            return $ms->sendMessage();
        }

        // Download the file
        $response = Request::getFile(['file_id' => $document->getFileId()]);
        if (!Request::downloadFile($response->getResult())) {
            $ms->prepareMessage($chat_id, 'There was an error obtaining your file. Please send it again.', null, $selective_reply);
            return $ms->sendMessage();
        }

        // Extract the milestones and store them in the database
        $file_path = $this->telegram->getDownloadPath() . '/' . $response->getResult()->getFilePath();
        $xmlManCon = new XmlUtils();
        try {
            $milestones = $xmlManCon->extractStoreMilestones($file_path, $chat->getId());
        } catch (NoMilestonesException $e) {
            $ms->prepareMessage($chat_id, $e->getMessage(), null, $selective_reply);
            return $ms->sendMessage();
        } catch (IncorrectFileException $e) {
            $ms->prepareMessage($chat_id, $e->getMessage(), null, $selective_reply);
            return $ms->sendMessage();
        }
        $this->conversation->stop();
        $ms->prepareMessage($chat_id, $this->prepareFormattedMessage($milestones), 'HTML', $selective_reply);
        return $ms->sendMessage();
    }
}
