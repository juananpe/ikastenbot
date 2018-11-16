<?php

declare(strict_types=1);

namespace TelegramBotGanttProject\Service;

use Doctrine\ORM\EntityManager;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Telegram;
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
    private function notifyUsersMilestonesToday()
    {
        $results = $this->findMilestonesReachToday();
        foreach ($results as $milestone) {
            $text = $this->twig->render('notifications/singleMilestoneNotification.txt.twig', [
                'milestones' => $results
            ]);

            $this->mss->prepareMessage((int)$milestone->getChat_id(), $text, 'HTML');
            $this->mss->sendMessage();
        }
    }

    /**
     * Notify users about milestones that are close to be reached according
     * to their planning.
     */
    private function notifyUsersMilestonesClose()
    {
        $results = $this->findMilestonesToNotifyAbout();

        foreach ($results as $row) {
            $text = $this->twig->render('notifications/multipleMilestoneNotification.txt.twig', [
                'milestones'    => [$row[0]],
                'days_left'     => $row[1]
            ]);

            $this->mss->prepareMessage((int)$row[0]->getChat_id(), $text, 'HTML');
            $this->mss->sendMessage();
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
