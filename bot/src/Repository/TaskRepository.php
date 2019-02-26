<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Task;
use Doctrine\ORM\EntityRepository;

class TaskRepository extends EntityRepository
{
    /**
     * The function to be used in order to calculate the difference between
     * dates.
     */
    const DATEDIFFFUNCTION = 'DATE_DIFF(t.date, CURRENT_DATE())';

    /**
     * Finds Tasks that are to be reached today.
     *
     * @param bool $restrictToMilestones Restrict search to milestones
     *                                   only
     *
     * @return Task[] Array of tasks
     */
    public function findTasksReachToday(bool $restrictToMilestones = false): array
    {
        $qb = $this->_em->createQueryBuilder();

        $qb->select('t')
            ->from(Task::class, 't')
            ->where(self::DATEDIFFFUNCTION.' = 0')
            ->andWhere('t.notify = 1')
        ;

        if ($restrictToMilestones) {
            $qb->andWhere('t.isMilestone = 1');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Finds Tasks that are to be reached in 30, 15, 3, 2 or 1 days.
     *
     * @param bool $restrictToMilestones Restrict search to milestones only
     *
     * @return task[][] Nested array of Tasks and their corresponding days to
     *                  be reached
     */
    public function findTasksToNotifyAbout(bool $restrictToMilestones = false)
    {
        $qb = $this->_em->createQueryBuilder();

        $qb->select('t', self::DATEDIFFFUNCTION)
            ->from(Task::class, 't')
            ->where(self::DATEDIFFFUNCTION.' IN (1, 2, 3, 15, 30)')
            ->andWhere('t.notify = 1')
            ->orderBy(self::DATEDIFFFUNCTION)
        ;

        if ($restrictToMilestones) {
            $qb->andWhere('t.isMilestone = 1');
        }

        return $qb->getQuery()->getResult();
    }
}