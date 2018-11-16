<?php

declare(strict_types=1);

namespace TelegramBotGanttProject\Service;

use Doctrine\ORM\EntityManager;
use TelegramBotGanttProject\Entity\Milestone;
use TelegramBotGanttProject\Service\MessageSenderService;
use Twig\Environment as TemplatingEngine;
use Twig\Loader\FilesystemLoader;

class MilestoneReminderService
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
     * Twig templating engine
     *
     * @var TemplatingEngine
     */
    protected $twig;

    /**
     * Message sender service
     *
     * @var MessageSenderService
     */
    protected $mss;

    /**
     * Construct MilestoneReminderService object
     *
     * @param EntityManager         $em     Doctrine entity manager
     * @param MessageSenderService  $mss    Message sender service
     * @param Twig                  $twig   Templating engine
     */
    public function __construct(EntityManager $em, MessageSenderService $mss, TemplatingEngine $twig)
    {
        $this->em = $em;
        $this->mss = $mss;
        $this->twig = $twig;
    }

    /**
     * Finds milestones that are to be reached in 30, 15, 3 or 2 days.
     *
     * @return Milestone[] Array of Milestones
     */
    public function findMilestonesToNotifyAbout()
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('m', self::DATEDIFFFUNCTION)
            ->from(Milestone::class, 'm')
            ->where(self::DATEDIFFFUNCTION . ' = 30')
            ->orWhere(self::DATEDIFFFUNCTION . ' = 15')
            ->orWhere(self::DATEDIFFFUNCTION . ' BETWEEN 2 AND 3')
            ->orderBy(self::DATEDIFFFUNCTION);

        return $qb->getQuery()->getResult();
    }

    /**
     * Finds milestones that are to be reached today.
     *
     * @return mixed[Milestone][int] Nested array of Milestones and their
     *                               corresponding days to be reached.
     */
    public function findMilestonesReachToday()
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('m')
            ->from(Milestone::class, 'm')
            ->where(self::DATEDIFFFUNCTION . ' = 0');

        return $qb->getQuery()->getResult();
    }

    /**
     * Notify users about the milestones they should reach today according to
     * their planning.
     */
    public function notifyUsersMilestonesToday()
    {
        $milestones = $this->findMilestonesReachToday();

        $text = $this->twig->render('notifications/milestoneTodayText.twig');
        $text .= PHP_EOL;

        foreach ($milestones as $milestone) {
            $text .= $this->twig->render('notifications/milestone.twig', [
                'milestone' => $milestone
            ]);
            $text .= PHP_EOL;
        }

        $this->mss->prepareMessage((int)$milestone->getChat_id(), $text, 'HTML');
        $this->mss->sendMessage();
    }

    /**
     * Notify users about milestones that are close to be reached according
     * to their planning.
     */
    public function notifyUsersMilestonesClose()
    {
        $results = $this->findMilestonesToNotifyAbout();

        $text = $this->twig->render('notifications/milestonesCloseText.twig');
        $text .= PHP_EOL;

        foreach ($results as $row) {
            $text .= $this->twig->render('notifications/milestone.twig', [
                'milestone' => $row[0],
                'daysLeft'  => $row[1]
            ]);
            $text .= PHP_EOL;
        }

        $this->mss->prepareMessage((int)$row[0]->getChat_id(), $text, 'HTML');
        $this->mss->sendMessage();
    }
}
