<?php

declare(strict_types=1);

namespace IkastenBot\Utils;

use Doctrine\ORM\EntityManager;
use IkastenBot\Entity\Task;

class TaskUtils
{
    /**
     * Entity Manager.
     *
     * @var EntityManager
     */
    protected $em;

    public function __construct(EntityManager $em = null)
    {
        $this->em = $em;
    }

    /**
     * Modifies a task's duration.
     *
     * @param Task $task     The task to modify
     * @param int  $duration The duration offset —negative or positive— to apply to
     *                       the task
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
