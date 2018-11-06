<?php

declare(strict_types=1);

// Longman's namespace must be used as otherwise the command is not recognized
namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\Update;
use MikelAlejoBR\TelegramBotGanttProject\Controller\XmlManagerController;
use MikelAlejoBR\TelegramBotGanttProject\Exception\NoMilestonesException;

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
     * Prepare a formatted message with the tasks to be reminded of
     *
     * @param array $tasks Array of Task objects
     * @return string      Formatted message in HTML
     */
    private function prepareFormattedMessage(array $tasks): string
    {
        $message = 'You will be reminded about the following milestones:' . PHP_EOL;
        foreach ($tasks as $task) {
            $message .= '<b>Milestone name:</b> ' . $task->getName() . PHP_EOL;
            $message .= '<b>Start date:</b> ' . $task->getStart()->format('Y-m-d H:i:s') . PHP_EOL;
            $message .= '<b>Finish date:</b> ' . $task->getFinish()->format('Y-m-d H:i:s') . PHP_EOL;
            $message .= PHP_EOL;
        }

        return $message;
    }

    public function execute()
    {
        $message = $this->getMessage();

        $chat    = $message->getChat();
        $user    = $message->getFrom();
        $text    = $message->getText(true);
        if (!empty($text)) {
            $text = trim($text);
        }
        $chat_id = $chat->getId();
        $user_id = $user->getId();

        $data = [
            'chat_id' => $chat_id,
        ];

        if ($chat->isGroupChat() || $chat->isSuperGroup()) {
            //reply to message id is applied by default
            //Force reply is applied by default so it can work with privacy on
            $data['reply_markup'] = Keyboard::forceReply(['selective' => true]);
        }

        $this->conversation = new Conversation($user_id, $chat_id, $this->getName());

        $notes = &$this->conversation->notes;
        !is_array($notes) && $notes = [];

        //cache data from the tracking session if any
        $state = 0;
        if (isset($notes['state'])) {
            $state = $notes['state'];
        }

        $result = Request::emptyResponse();

        switch ($state) {
            case 0:
                $document = $message->getDocument();
                if (null === $document) {
                    $notes['state'] = 0;
                    $this->conversation->update();

                    $data['text']           = 'Please send your GanttProject\'s XML file.';
                    $data['reply_markup']   = Keyboard::remove(['selective' => true]);

                    $result = Request::sendMessage($data);
                    break;
                }

                $document_id        = $document->getFileId();

                $response = Request::getFile(['file_id' => $document_id]);
                if (!Request::downloadFile($response->getResult())) {
                    $data['text']           = 'There was an error obtaining your file. Please send it again.';
                    $data['reply_markup']   = Keyboard::remove(['selective' => true]);

                    $result = Request::sendMessage($data);
                    break;
                }

                $xmlManCon = new XmlManagerController();

                $file_path = $this->telegram->getDownloadPath() . '/' . $response->getResult()->getFilePath();
                try {
                    $tasks = $xmlManCon->extractStoreTasks($file_path, $user);
                    $data['text']           = $this->prepareFormattedMessage($tasks);
                    $data['parse_mode']     = 'HTML';
                    $data['reply_markup']   = Keyboard::remove(['selective' => true]);

                    $result = Request::sendMessage($data);
                    $this->conversation->stop();
                } catch (NoMilestonesException $e) {
                    $data['text']           = 'There were no milestones in the file you provided.';
                    $data['reply_markup']   = Keyboard::remove(['selective' => true]);

                    $result = Request::sendMessage($data);
                    break;
                }
        }

        return $result;
    }
}
