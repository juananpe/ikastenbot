<?php

declare(strict_types=1);

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
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;


class TestCommand extends UserCommand
{

	/**
	 * {@inheritdoc}
	 */
	protected $name = 'test';

	/**
	 * {@inheritdoc}
	 */
	protected $description = 'testing...';

	/**
	 * {@inheritdoc}
	 */
	protected $usage = '/test';

	/**
	 * {@inheritdoc}
	 */
	protected $version = '1.0.0';



	public function execute()
	{
		$messageFormatterUtils = new MessageFormatterUtils();

		$message = $this->getMessage();

		$text = '';
		$text = trim($message->getText(true));
		$user = $message->getFrom();

		$chat = $message->getChat();
		$chat_id = $chat->getId();


		$user_id = $user->getId();

		$ms = new MessageSenderService();
		$db = new DoctrineBootstrap();
		$em = $db->getEntityManager();

		$cmd = 'sudo -u juanan /usr/local/bin/docker exec gp xvfb-run /ganttproject-2.8.10-r2364/ganttproject -export png -o /tmp/gp/$FILE.png /tmp/gp/$FILE';
		// $cmd = 'sudo -u juanan whoami';
		$process = \method_exists(Process::class, 'fromShellCommandline') ? Process::fromShellCommandline($cmd) : new Process($cmd);
		$process->run(null, ['FILE' => $text]);

		if (!$process->isSuccessful()){
			throw new ProcessFailedException($process);
		}


		$output =  $process->getOutput();

		$ms->prepareMessage($chat_id, $output);

		return $ms->sendMessage();
	}
}
