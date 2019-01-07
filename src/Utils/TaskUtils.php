<?php

declare(strict_types=1);

namespace IkastenBot\Utils;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
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
        if (\is_null($em)) {
            $config = Setup::createAnnotationMetadataConfiguration(array(PROJECT_ROOT . "/../Entity/"), false);

            $connectionParams = [
                'driver' => 'pdo_mysql',
                'pdo' => DB::getPdo()
            ];
            $this->em = EntityManager::create($connectionParams, $config);
        } else {
            $this->em = $em;
        }
    }

    /**
     * Modifies a task's duration
     *
     * @param   integer $taskId     The id of the task
     * @param   integer $duration   The duration offset —negative or positive— to apply to
     *                              the task
     * @return  void
     *
     * @throws TaskNotFoundException When the task doesn't exist
     */
    public function modifyTaskDuration(int $taskId, int $durationOffset): void
    {
        $qb = $this->em->createQueryBuilder();

        $qb
            ->update(Task::class, 't')
            ->set('t.duration', 't.duration + :duration')
            ->where('t.id = :task_id')
            ->setParameter(':duration', $durationOffset)
            ->setParameter(':task_id', $taskId)
        ;

        if (!$qb->getQuery()->getResult()) {
            throw new TaskNotFoundException(
                'The specified task doesn\'t exist.'
            );
        }
    }
}
