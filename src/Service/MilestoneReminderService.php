<?php

declare(strict_types=1);

namespace MikelAlejoBR\TelegramBotGanttProject\Service;

use Doctrine\ORM\EntityManager;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Telegram;
use MikelAlejoBR\TelegramBotGanttProject\Entity\Milestone;
use MikelAlejoBR\TelegramBotGanttProject\Service\MessageSenderService;
use Twig\Environment as TemplatingEngine;
use Twig\Loader\FilesystemLoader;

class MilestoneReminderService
{
    /**
     * The function to be used in order to calculate the difference between
     * dates
     */
    const DATEDIFFFUNCTION = 'DATE_DIFF(m.start, CURRENT_DATE())';

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
    private function findMilestonesToNotifyAbout()
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
    private function findMilestonesReachToday()
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
    private function notifyUsersMilestonesToday()
    {
        $results = $this->findMilestonesReachToday();
        foreach ($results as $milestone) {
            $text = $this->twig->render('notifications/singleMilestoneNotification.txt.twig', [
                'milestones' => $results
            ]);

            $this->mss->sendSimpleMessage($milestone->getUser_id(), $text, 'HTML');
        }
    }

    /**
     * Notify users about milestones that are close to be reached according
     * to their planning.
     */
    private function notifyUsersMilestonesClose()
    {
        $results = $this->findMilestonesToNotifyAbout();
        $text = 'This is a reminder to inform you that the following '
            . 'milestones are relatively close to be reached. The details of '
            . 'these milestones are:' . PHP_EOL . PHP_EOL;

        foreach ($results as $row) {
            $text = $this->twig->render('notifications/multipleMilestoneNotification.txt.twig', [
                'milestones'    => [$row[0]],
                'days_left'     => $row[1]
            ]);

            $this->mss->sendSimpleMessage($row[0]->getUser_id(), $text, 'HTML');
        }
    }

    /**
     * Notify users about their milestones that are as close as 30, 15, 3, 2 or
     * 1 days.
     */
    public function notifyUsers()
    {
        $telegramBot = new Telegram(getenv('TELEGRAM_BOT_API_KEY'), getenv('TELEGRAM_BOT_USERNAME'));

        $this->notifyUsersMilestonesToday();
        $this->notifyUsersMilestonesClose();
    }
}
