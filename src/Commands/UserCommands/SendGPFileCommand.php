<?php

declare(strict_types=1);

// Longman's namespace must be used as otherwise the command is not recognized
namespace Longman\TelegramBot\Commands\UserCommands;

use IkastenBot\Exception\IncorrectFileException;
use IkastenBot\Exception\NoMilestonesException;
use IkastenBot\Service\MessageSenderService;
use IkastenBot\Utils\MessageFormatterUtils;
use IkastenBot\Utils\XmlUtils;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Commands\UserCommand;

class SendGpFileCommand extends UserCommand
{
    /**
     * @inheritDoc
     */
    protected $name         = 'sendgpfile';

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
     * Prepare a formatted message with the milestones to be reminded of
     *
     * @param   IkastenBot\Entity\Milestone[] $milestones   Array of Milestone objects
     * @return  string                                      Formatted message in HTML
     */
    private function prepareFormattedMessage(array $milestones): string
    {
        $mf = new MessageFormatterUtils();

        $text = 'You will be reminded of the following milestones:';
        $text .= PHP_EOL . PHP_EOL;

        foreach ($milestones as $milestone) {
            $mf->appendMilestone($text, $milestone);
        }

        return $text;
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
        unlink($file_path);
        $this->conversation->stop();
        $ms->prepareMessage($chat_id, $this->prepareFormattedMessage($milestones), 'HTML', $selective_reply);
        return $ms->sendMessage();
    }
}
