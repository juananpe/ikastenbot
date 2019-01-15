<?php

declare(strict_types=1);

namespace IkastenBot\Utils;

use Doctrine\ORM\EntityManager;
use IkastenBot\Entity\DoctrineBootstrap;
use IkastenBot\Entity\Task;
use IkastenBot\Exception\TaskNotFoundException;
use Longman\TelegramBot\DB;

class TaskUtils
{
    /**
     * Entity Manager
     *
     * @var EntityManager
     */
    protected $em;

    public function __construct(EntityManager $em = null)
    {
        $this->em = $em;
    }

    /**
     * Modifies a task's duration
     *
     * @param   Task    $task       The task to modify
     * @param   integer $duration   The duration offset —negative or positive— to apply to
     *                              the task
     * @return  void
     */
    public function modifyTaskDuration(Task $task, int $durationOffset): void
    {
        $task->setDuration(
            $task->getDuration() + $durationOffset
        );

        $this->em->persist($task);
        $this->em->flush();

    }
}
