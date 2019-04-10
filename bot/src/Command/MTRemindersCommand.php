<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\MessageFormatterUtilsService;
use App\Service\MessageSenderService;
use App\Service\TaskReminderService;
use Doctrine\ORM\EntityManagerInterface;
use Longman\TelegramBot\Telegram;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MTRemindersCommand extends Command
{
    /**
     * Command's name.
     *
     * @var string
     */
    protected static $defaultName = 'app:mt-send-reminders';

    /**
     * Entity manager.
     *
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * Message formatter utils.
     *
     * @var MessageFormatterUtilsService
     */
    protected $messageFormatterUtils;

    /**
     * Message sender service.
     *
     * @var MessageSenderService
     */
    protected $messageSender;

    /**
     * Task reminder service.
     *
     * @var TaskReminderService
     */
    protected $taskReminderService;

    public function __construct(EntityManagerInterface $entityManager, MessageFormatterUtilsService $messageFormatterUtils, MessageSenderService $messageSender, TaskReminderService $taskReminderService)
    {
        $this->entityManager = $entityManager;
        $this->messageFormatterUtils = $messageFormatterUtils;
        $this->messageSender = $messageSender;
        $this->taskReminderService = $taskReminderService;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Sends milestone or task notifications to users')
            ->setHelp('It allows you sending reminders or notifications to the '.
                        'users who have tasks or milestones to be reached close.')
            ->addOption(
                'milestones',
                null,
                InputOption::VALUE_NONE,
                'Restrict notifications to only milestones'
            )
            ->addOption(
                'tasks',
                null,
                InputOption::VALUE_NONE,
                'Restrict notifications to only tasks'
            )
            ->addOption(
                'today',
                null,
                InputOption::VALUE_NONE,
                'Restrict notifications to elements that are to be reached today'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Setup Telegram object
        $telegram = new Telegram(getenv('TELEGRAM_BOT_API_KEY'), getenv('TELEGRAM_BOT_USERNAME'));

        // Get the options
        $milestones = $input->getOption('milestones');
        $tasks = $input->getOption('tasks');
        $today = $input->getOption('today');

        // Check if neither of 'milestones' or 'tasks' options were specified
        $notSpecified = !($milestones || $tasks);

        // If not specified, notifications are sent for all elements
        $milestones = $milestones || $notSpecified;
        $tasks = $tasks || $notSpecified;

        // Check today's restriction
        $today = $input->getOption('today');

        if ($today) {
            if ($milestones) {
                $this->taskReminderService->notifyUsersMilestonesToday();
                $output->writeln('Notifications for today\'s milestones sent!');
            }

            if ($tasks) {
                $this->taskReminderService->notifyUsersTasksToday();
                $output->writeln('Notifications for today\'s tasks sent!');
            }
        } else {
            if ($milestones) {
                $this->taskReminderService->notifyUsersMilestonesToday();
                $this->taskReminderService->notifyUsersMilestonesClose();
                $output->writeln('Notifications about the relevant milestones\'s deadlines sent!');
            }

            if ($tasks) {
                $this->taskReminderService->notifyUsersTasksToday();
                $this->taskReminderService->notifyUsersTasksClose();
                $output->writeln('Notifications about the relevant tasks\'s deadlines sent!');
            }
        }
    }
}
