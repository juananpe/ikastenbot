<?php

declare(strict_types=1);

// Longman's namespace must be used as otherwise the command is not recognized
namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\Update;
use MikelAlejoBR\TelegramBotGanttProject\Exception\IncorrectFileException;
use MikelAlejoBR\TelegramBotGanttProject\Exception\NoMilestonesException;
use MikelAlejoBR\TelegramBotGanttProject\Service\MessageSenderService;
use MikelAlejoBR\TelegramBotGanttProject\Utils\XmlUtils;
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
     * The chat to which the response will be sent
     *
     * @var Chat
     */
    protected $chat;

    /**
     * The chat id to which the response will be sent
     *
     * @var int
     */
    protected $chat_id;

    /**
     * Is selective reply enabled?
     *
     * @var bool
     */
    protected $selective_reply;

    /**
     * The user who sent the message
     *
     * @var User
     */
    protected $user;

    /**
     * Twig templating engine
     *
     * @var Environment
     */
    protected $twig;

    /**
     * @inheritDoc
     */
    public function __construct(Telegram $telegram, Update $update = null)
    {
        parent::__construct($telegram, $update);

        $this->chat     = $this->getMessage()->getChat();
        $this->chat_id  = $this->chat->getId();

        //reply to message id is applied by default
        //Force reply is applied by default so it can work with privacy on
        $this->selective_reply = $this->chat->isGroupChat() || $this->chat->isSuperGroup();

        $this->user = $this->getMessage()->getFrom();
        $user_id    = $this->user->getId();

        $this->conversation = new Conversation($user_id, $this->chat_id, $this->getName());

        $loader = new FilesystemLoader(__DIR__ . '/../../templates/');
        $this->twig = new Environment($loader, array(
            'cache' => __DIR__ . '/../../var/cache/',
        ));
    }

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
        $ms = new MessageSenderService();

        // Get the sent document
        $document = $this->getMessage()->getDocument();
        if (null === $document) {
            $this->conversation->update();
            return $ms->sendSimpleMessage($this->chat_id, 'Please send your GanttProject\'s XML file.', null, $this->selective_reply);
        }

        // Download the file
        $response = Request::getFile(['file_id' => $document->getFileId()]);
        if (!Request::downloadFile($response->getResult())) {
            return $ms->sendSimpleMessage($this->chat_id, 'There was an error obtaining your file. Please send it again.', null, $this->selective_reply);
        }

        // Extract the milestones and store them in the database
        $file_path = $this->telegram->getDownloadPath() . '/' . $response->getResult()->getFilePath();
        $xmlManCon = new XmlUtils();
        try {
            $milestones = $xmlManCon->extractStoreMilestones($file_path, $this->chat);
        } catch (NoMilestonesException $e) {
            return $ms->sendSimpleMessage($this->chat_id, $e->getMessage(), null, $this->selective_reply);
        } catch (IncorrectFileException $e) {
            return $ms->sendSimpleMessage($this->chat_id, $e->getMessage(), null, $this->selective_reply);
        }
        $this->conversation->stop();
        return $ms->sendSimpleMessage($this->chat_id, $this->prepareFormattedMessage($milestones), 'HTML', $this->selective_reply);
    }
}
