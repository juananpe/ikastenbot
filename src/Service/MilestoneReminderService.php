<?php

declare(strict_types=1);

namespace IkastenBot\Service;

use IkastenBot\Entity\Milestone;
use IkastenBot\Service\MessageSenderService;
use IkastenBot\Utils\MessageFormatterUtils;
use Doctrine\ORM\EntityManager;

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
     * Message sender service
     *
     * @var MessageSenderService
     */
    protected $mss;

    /**
     * Construct MilestoneReminderService object
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
     * Finds milestones that are to be reached in 30, 15, 3 or 2 days.
     *
     * @return Milestone[][]    Nested array of Milestones and their
     *                          corresponding days to be reached.
     */
    public function findMilestonesToNotifyAbout(): array
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
     * @return Milestone[]
     */
    public function findMilestonesReachToday(): array
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
     *
     * @return void
     */
    public function notifyUsersMilestonesToday(): void
    {
        $milestones = $this->findMilestonesReachToday();

        foreach ($milestones as $milestone) {
            $text = '';
            $this->mf->appendTwigFile($text, 'notifications/milestoneTodayText.twig');
            $this->mf->appendMilestone($text, $milestone);

            $this->mss->prepareMessage((int)$milestone->getChat_id(), $text, 'HTML');
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
        $results = $this->findMilestonesToNotifyAbout();

        foreach ($results as $row) {
            $text = '';
            $this->mf->appendTwigFile($text, 'notifications/milestonesCloseText.twig');
            $this->mf->appendMilestone($text, $row[0], $row[1]);

            $this->mss->prepareMessage((int)$row[0]->getChat_id(), $text, 'HTML');
            $this->mss->sendMessage();
        }
    }
}
