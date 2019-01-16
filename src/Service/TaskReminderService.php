<?php

declare(strict_types=1);

namespace IkastenBot\Service;

use IkastenBot\Entity\Task;
use IkastenBot\Service\MessageSenderService;
use IkastenBot\Utils\MessageFormatterUtils;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Doctrine\ORM\EntityManager;

class TaskReminderService
{
    /**
     * The function to be used in order to calculate the difference between
     * dates
     */
    const DATEDIFFFUNCTION = 'DATE_DIFF(m.date, CURRENT_DATE())';

    /**
     * Entity manager
     *
     * @var EntityManager
     */
    protected $em;

    /**
     * Message sender service
     *
     * @var MessageSenderService
     */
    protected $mss;

    /**
     * Construct TaskReminderService object
     *
     * @param EntityManager         $em     Doctrine entity manager
     * @param MessageFormatterUtils $mfu    Message formatter utils
     * @param MessageSenderService  $mss    Message sender service
     */
    public function __construct(EntityManager $em, MessageFormatterUtils $mfu, MessageSenderService $mss)
    {
        $this->em = $em;
        $this->mf = $mfu;
        $this->mss = $mss;
    }

    /**
     * Notify users about the tasks they should reach today according to
     * their planning.
     *
     * @return void
     */
    public function notifyUsersTasksToday(): void
    {
        $tasks = $this->em->getRepository(Task::class)->findTasksReachToday();

        foreach ($tasks as $task) {
            $text = '';
            $this->mf->appendTwigFile($text, 'notifications/task/taskTodayText.twig');
            $this->mf->appendTask($text, $task);

            $keyboard = new InlineKeyboard(
                [
                    [
                        'text' => 'Delay task',
                        'callback_data' => '/delaytask ' . $task->getId()
                    ]
                ]
            );

            $this->mss->prepareMessage((int)$task->getChat_id(), $text, 'HTML', null, $keyboard);
            $this->mss->sendMessage();
        }
    }

    /**
     * Notify users about tasks that are close to be reached according
     * to their planning.
     *
     * @return void
     */
    public function notifyUsersTasksClose(): void
    {
        $results = $this->em->getRepository(Task::class)->findTasksToNotifyAbout();

        foreach ($results as $row) {
            $text = '';
            $this->mf->appendTwigFile($text, 'notifications/task/tasksCloseText.twig');
            $this->mf->appendTask($text, $row[0], $row[1]);

            $keyboard = new InlineKeyboard(
                [
                    [
                        'text' => 'Delay task',
                        'callback_data' => '/delaytask ' . $row[0]->getId()
                    ]
                ]
            );

            $this->mss->prepareMessage((int)$row[0]->getChat_id(), $text, 'HTML', null, $keyboard);
            $this->mss->sendMessage();
        }
    }

     /**
     * Notify users about the milestones they should reach today according to
     * their planning.
     *
     * @return void
     */
    public function notifyUsersMilestonesToday(): void
    {
        $milestones = $this->em->getRepository(Task::class)->findTasksReachToday(true);

        foreach ($milestones as $milestone) {
            $text = '';
            $this->mf->appendTwigFile($text, 'notifications/milestone/milestoneTodayText.twig');
            $this->mf->appendTask($text, $milestone, null, $milestone->getIsMilestone());

            $keyboard = new InlineKeyboard(
                [
                    [
                        'text' => 'Delay task',
                        'callback_data' => '/delaytask ' . $milestone->getId()
                    ]
                ]
            );

            $this->mss->prepareMessage((int)$milestone->getChat_id(), $text, 'HTML', null, $keyboard);
            $this->mss->sendMessage();
        }
    }

    /**
     * Notify users about milestones that are close to be reached according
     * to their planning.
     *
     * @return void
     */
    public function notifyUsersMilestonesClose(): void
    {
        $results = $this->em->getRepository(Task::class)->findTasksToNotifyAbout(true);

        foreach ($results as $row) {
            $text = '';
            $this->mf->appendTwigFile($text, 'notifications/milestone/milestonesCloseText.twig');
            $this->mf->appendTask($text, $row[0], $row[1], $row[0]->getIsMilestone());

            $keyboard = new InlineKeyboard(
                [
                    [
                        'text' => 'Delay task',
                        'callback_data' => '/delaytask ' . $row[0]->getId()
                    ]
                ]
            );

            $this->mss->prepareMessage((int)$row[0]->getChat_id(), $text, 'HTML', null, $keyboard);
            $this->mss->sendMessage();
        }
    }
}
