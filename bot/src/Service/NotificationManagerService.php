<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\GanttProject;
use App\Entity\Task;
use Doctrine\ORM\EntityManager;

class NotificationManagerService
{
    /**
     * Entity manager.
     *
     * @var EntityManager
     */
    protected $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Finds tasks belonging to previous versions of a given GanttProject, and
     * disables notifications for them.
     *
     * @param int          $chatId       The chat id of the tasks
     * @param GanttProject $ganttProject The latest and newest GanttProject
     */
    public function disableNotificationsForOutdatedTasks(int $chatId, GanttProject $ganttProject)
    {
        $outdatedTasks = $this->entityManager
            ->getRepository(Task::class)
            ->findFromPreviousVersionsOfGanttProject($chatId, $ganttProject)
        ;

        foreach ($outdatedTasks as $task) {
            $task->setNotify(false);
            $this->entityManager->persist($task);
        }

        $this->entityManager->flush();
    }
}
