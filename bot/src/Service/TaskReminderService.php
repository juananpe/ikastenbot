<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Task;
use Doctrine\ORM\EntityManagerInterface;

class TaskReminderService
{
    /**
     * The function to be used in order to calculate the difference between
     * dates.
     */
    const DATEDIFFFUNCTION = 'DATE_DIFF(m.date, CURRENT_DATE())';

    /**
     * Entity manager.
     *
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * Message sender service.
     *
     * @var MessageSenderService
     */
    protected $mss;

    /**
     * Construct TaskReminderService object.
     *
     * @param EntityManagerInterface       $em  Doctrine entity manager
     * @param MessageFormatterUtilsService $mfu Message formatter utils
     * @param MessageSenderService         $mss Message sender service
     */
    public function __construct(EntityManagerInterface $em, MessageFormatterUtilsService $mfu, MessageSenderService $mss)
    {
        $this->em = $em;
        $this->mf = $mfu;
        $this->mss = $mss;
    }

    /**
     * Notify users about the tasks they should reach today according to
     * their planning.
     */
    public function notifyUsersTasksToday(): void
    {
        $tasks = $this->em->getRepository(Task::class)->findTasksReachToday();

        foreach ($tasks as $task) {
            $text = '';
            $this->mf->appendTwigFile($text, 'notifications/task/taskTodayText.twig');
            $this->mf->appendTask($text, $task);

            $keyboard = $this->mf->createThreeOptionInlineKeyboard(
                $task->getId(),
                false
            );

            $this->mss->prepareMessage((int) $task->getChat_id(), $text, 'HTML', null, $keyboard);
            $this->mss->sendMessage();
        }
    }

    /**
     * Notify users about tasks that are close to be reached according
     * to their planning.
     */
    public function notifyUsersTasksClose(): void
    {
        $results = $this->em->getRepository(Task::class)->findTasksToNotifyAbout();

        foreach ($results as $row) {
            $text = '';

            // Append the text to the message to be sent
            $parameters = [
                'task' => $row[0],
                'daysLeft' => $row[1],
            ];

            $this->mf->appendTwigFileWithParameters(
                $text,
                'notifications/notifyTaskMilestoneClose.twig',
                $parameters
            );

            $keyboard = $this->mf->createThreeOptionInlineKeyboard(
                $row[0]->getId(),
                $row[0]->getIsMilestone()
            );

            $this->mss->prepareMessage((int) $row[0]->getChat_id(), $text, 'HTML', null, $keyboard);
            $this->mss->sendMessage();
        }
    }

    /**
     * Notify users about the milestones they should reach today according to
     * their planning.
     */
    public function notifyUsersMilestonesToday(): void
    {
        $milestones = $this->em->getRepository(Task::class)->findMilestonesReachToday();

        foreach ($milestones as $milestone) {
            $text = '';
            $this->mf->appendTwigFile($text, 'notifications/milestone/milestoneTodayText.twig');
            $this->mf->appendTask($text, $milestone, null, $milestone->getIsMilestone());

            $keyboard = $this->mf->createThreeOptionInlineKeyboard(
                $milestone->getId(),
                true
            );

            $this->mss->prepareMessage((int) $milestone->getChat_id(), $text, 'HTML', null, $keyboard);
            $this->mss->sendMessage();
        }
    }

    /**
     * Notify users about milestones that are close to be reached according
     * to their planning.
     */
    public function notifyUsersMilestonesClose(): void
    {
        $results = $this->em->getRepository(Task::class)->findMilestonesToNotifyAbout();

        foreach ($results as $row) {
            $text = '';

            $parameters = [
                'task' => $row[0],
                'daysLeft' => $row[1],
            ];

            $this->mf->appendTwigFileWithParameters(
                $text,
                'notifications/notifyTaskMilestoneClose.twig',
                $parameters
            );

            $keyboard = $this->mf->createThreeOptionInlineKeyboard(
                $row[0]->getId(),
                $row[0]->getIsMilestone()
            );

            $this->mss->prepareMessage((int) $row[0]->getChat_id(), $text, 'HTML', null, $keyboard);
            $this->mss->sendMessage();
        }
    }
}
