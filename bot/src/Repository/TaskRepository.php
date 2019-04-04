<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\GanttProject;
use App\Entity\Task;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class TaskRepository extends EntityRepository
{
    /**
     * The function to be used in order to calculate the difference between
     * dates.
     */
    const DATEDIFFFUNCTION = 'DATE_DIFF(t.date, CURRENT_DATE())';

    /**
     * Finds tasks that are to be reached today.
     *
     * @return Task[] Array of tasks
     */
    public function findTasksReachToday(): array
    {
        $queryBuilder = $this->getTasksReachTodayQueryBuilder();

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Find tasks that are milestones and are to be reached today.
     *
     * @return Task[] Array of tasks that are milestones
     */
    public function findMilestonesReachToday(): array
    {
        $queryBuilder = $this->getTasksReachTodayQueryBuilder();

        $queryBuilder->andWhere('t.isMilestone = 1');

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Finds tasks that are to be reached in 30, 15, 3, 2 or 1 days from today.
     *
     * @return Task[][] Nested array of tasks and their corresponding days to
     *                  be reached
     */
    public function findTasksToNotifyAbout(): array
    {
        $queryBuilder = $this->getTasksReachCloseQueryBuilder();

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Finds tasks that are milestones and are to be reached in 30, 15, 3, 2 or
     * 1 days from today.
     *
     * @return Task[][] Nested array of tasks that are milestones and their
     *                  corresponding days to be reached
     */
    public function findMilestonesToNotifyAbout(): array
    {
        $queryBuilder = $this->getTasksReachCloseQueryBuilder();

        $queryBuilder->andWhere('t.isMilestone = 1');

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Finds the tasks belonging to the previous versions of the given
     * GanttProject.
     *
     * @param int          $chatId       The chat id of the tasks to be fetched
     * @param GanttProject $ganttProject The newest GanttProject
     *
     * @return Task[] The resulting tasks
     */
    public function findFromPreviousVersionsOfGanttProject(int $chatId, GanttProject $ganttProject): array
    {
        $queryBuilder = $this->_em->createQueryBuilder();

        $queryBuilder
            ->select('t')
            ->from(Task::class, 't')
            ->join('t.ganttProject', 'g')
            ->where('t.chat_id = :chatId')
            ->andWhere('t.notify = true')
            ->andWhere('g.version < :version')
            ->setParameter('chatId', $chatId)
            ->setParameter('version', $ganttProject->getVersion())
        ;

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Returns a query builder for tasks that are to be reached today and
     * have the notify flag on.
     *
     * @return QueryBuilder The resulting query builder
     */
    private function getTasksReachTodayQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->_em->createQueryBuilder();

        $queryBuilder->select('t')
            ->from(Task::class, 't')
            ->where(self::DATEDIFFFUNCTION.' = 0')
            ->andWhere('t.notify = 1')
        ;

        return $queryBuilder;
    }

    /**
     * Returns a query builder for tasks that are to be reach close and have
     * the notify flag on. Close means exactly 1, 2, 3, 15 or 30 days from
     * today.
     *
     * @return QueryBuilder The resulting query builder
     */
    private function getTasksReachCloseQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->_em->createQueryBuilder();

        $queryBuilder->select('t', self::DATEDIFFFUNCTION)
            ->from(Task::class, 't')
            ->where(self::DATEDIFFFUNCTION.' IN (1, 2, 3, 15, 30)')
            ->andWhere('t.notify = 1')
            ->orderBy(self::DATEDIFFFUNCTION)
        ;

        return $queryBuilder;
    }
}
