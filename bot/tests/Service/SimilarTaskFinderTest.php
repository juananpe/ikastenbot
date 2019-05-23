<?php

namespace App\Tests\Service;

use App\Entity\DoctrineBootstrap;
use App\Entity\Task;
use App\Service\SimilarTaskFinder;
use App\Service\StringComparator;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Service\SimilarTaskFinder
 *
 * @internal
 */
class SimilarTaskFinderTest extends TestCase
{
    /**
     * @var SimilarTaskFinder
     */
    private $stf;

    /**
     * @var array Tasks to analyze
     */
    private $targetTaskList;

    /**
     * @var array Tasks to compare with
     */
    private $dbTaskList;

    public function setUp()
    {
        $this->targetTaskList = [
            $this->createProxyTask(0, 0, 'Escribir la Memoria', 6),
            $this->createProxyTask(0, 0, 'Diseñar los Diagrama de clases', 2),
        ];

        $this->dbTaskList = [
            $this->createProxyTask(1, 0, 'Redacción de la memoria', 6),
            $this->createProxyTask(1, 1, 'Memoria', 2),
            $this->createProxyTask(1, 2, 'Memoria del TFG', 1),

            $this->createProxyTask(1, 3, 'Diseño de diagramas', 1),
            $this->createProxyTask(1, 4, 'Diagrama de clases', 3),

            $this->createProxyTask(1, 5, 'Foo Bar Eggs', 123),
            $this->createProxyTask(1, 6, 'Kvothe', 456),
            $this->createProxyTask(1, 7, 'Bolt the Bird', 789),
        ];

        $em = (new DoctrineBootstrap())->getEntityManager();
        $proxyEm = new ProxyEntityManager($em, $this->dbTaskList);
        $sc = new StringComparator();

        $this->stf = new SimilarTaskFinder($sc, $proxyEm);
    }

    /**
     * @covers \App\Service\SimilarTaskFinder::getSimilarTasksDurations()
     */
    public function testGetSimilarTasksDurations()
    {
        $similarTasksInfo = $this->stf->getSimilarTasksDurations($this->targetTaskList, $this->dbTaskList);

        self::assertEquals(count($similarTasksInfo), 2);

        self::assertEquals($similarTasksInfo[0]['taskName'], $this->targetTaskList[0]->getName());
        self::assertEquals($similarTasksInfo[0]['similarTasksCount'], 3);
        self::assertEquals($similarTasksInfo[0]['similarTasksAccDur'], 9);

        self::assertEquals($similarTasksInfo[1]['taskName'], $this->targetTaskList[1]->getName());
        self::assertEquals($similarTasksInfo[1]['similarTasksCount'], 2);
        self::assertEquals($similarTasksInfo[1]['similarTasksAccDur'], 4);
    }

    /**
     * @covers \App\Service\SimilarTaskFinder::getTasksWithAtypicalDuration()
     */
    public function testGetTasksWithAtypicalDuration()
    {
        $avgTasksDuration = $this->stf->getTasksWithAtypicalDuration($this->targetTaskList);

        self::assertEquals(count($avgTasksDuration), 1);

        self::assertEquals($avgTasksDuration[0]['taskName'], $this->targetTaskList[0]->getName());
        self::assertEquals($avgTasksDuration[0]['taskDuration'], $this->targetTaskList[0]->getDuration());
        self::assertEquals($avgTasksDuration[0]['avgDuration'], 9 / 3);
    }

    private function createProxyTask(int $chat_id, int $id, string $name, int $duration)
    {
        $task = new Task();
        $task->setChat_id($chat_id);
        $task->setId($id);
        $task->setName($name);
        $task->setDuration($duration);

        return $task;
    }
}

class ProxyEntityManager extends EntityManager
{
    private $taskList;

    public function __construct(EntityManager $em, array $taskList)
    {
        parent::__construct($em->getConnection(), $em->getConfiguration(), $em->getEventManager());
        $this->taskList = $taskList;
    }

    public function getRepository($entityName)
    {
        return new ProxyRepo($this, $this->getClassMetadata($entityName), $this->taskList);
    }
}

class ProxyRepo extends EntityRepository
{
    private $taskList;

    public function __construct(EntityManagerInterface $em, Mapping\ClassMetadata $class, array $taskList)
    {
        parent::__construct($em, $class);
        $this->taskList = $taskList;
    }

    public function findAll()
    {
        return $this->taskList;
    }
}
