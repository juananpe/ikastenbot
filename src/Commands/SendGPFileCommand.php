<?php

declare(strict_types=1);

// Longman's namespace must be used as otherwise the command is not recognized
namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\Update;
use MikelAlejoBR\TelegramBotGanttProject\Controller\XmlManagerController;
use MikelAlejoBR\TelegramBotGanttProject\Exception\NoMilestonesException;
use MikelAlejoBR\TelegramBotGanttProject\Service\MessageSenderService;
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
     * The chat id to which the response will be sent
     *
     * @var int
     */
    protected $chat_id;

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

        $chat           = $this->getMessage()->getChat();
        $this->chat_id  = $chat->getId();

        if ($chat->isGroupChat() || $chat->isSuperGroup()) {
            //reply to message id is applied by default
            //Force reply is applied by default so it can work with privacy on
            $this->data['reply_markup'] = Keyboard::forceReply(['selective' => true]);
        }

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
            return $ms->sendSimpleMessage($this->chat_id, 'Please send your GanttProject\'s XML file.');
        }

        // Download the file
        $response = Request::getFile(['file_id' => $document->getFileId()]);
        if (!Request::downloadFile($response->getResult())) {
            return $ms->sendSimpleMessage($this->chat_id, 'There was an error obtaining your file. Please send it again.');
        }

        // Extract the milestones and store them in the database
        $file_path = $this->telegram->getDownloadPath() . '/' . $response->getResult()->getFilePath();
        $xmlManCon = new XmlManagerController();
        try {
            $milestones = $xmlManCon->extractStoreMilestones($file_path, $this->user);
        } catch (NoMilestonesException $e) {
            return $ms->sendSimpleMessage($this->chat_id, 'There were no milestones in the file you provided.');
        }
        $this->conversation->stop();
        return $ms->sendSimpleMessage($this->chat_id, $this->prepareFormattedMessage($milestones), 'HTML');
    }
}
