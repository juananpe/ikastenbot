<?php

declare(strict_types=1);

namespace IkastenBot\Tests\Utils;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use IkastenBot\Entity\GanttProject;
use IkastenBot\Entity\Task;
use IkastenBot\Entity\User;
use IkastenBot\Exception\NoTasksException;
use IkastenBot\Tests\DatabaseTestCase;
use IkastenBot\Tests\Fixtures\GanttProjectDataLoader;
use IkastenBot\Tests\Fixtures\UserDataLoader;
use IkastenBot\Utils\XmlUtils;
use Longman\TelegramBot\Telegram;
use Symfony\Component\Yaml\Yaml;

/**
 * @internal
 * @coversNothing
 */
final class XmlUtilsDbTest extends DatabaseTestCase
{
    /**
     * Entity manager.
     *
     * @var Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * Test user.
     *
     * @var User
     */
    protected $user;

    /**
     * Test GanttProject.
     *
     * @var GanttProject
     */
    protected $ganttProject;
    /**
     * Directory path containing test files.
     *
     * @var string
     */
    private $dataDir;

    /**
     * Directory containing Gan XML files to be imported.
     *
     * @var string
     */
    private $ganDir;

    /**
     * XML Utils.
     *
     * @var XmlUtils
     */
    private $xu;

    /**
     * PDO object.
     *
     * @var PDO
     */
    private $pdo;

    public function setUp(): void
    {
        $this->dataDir = __DIR__.'/../_data/task_data/';
        $this->ganDir = $this->dataDir.'gan/';

        // Get entity manager
        $this->em = $this->getDoctrineEntityManager();

        // Get pdo
        $this->pdo = $this->em->getConnection()->getWrappedConnection();

        $insert_test_chat = 'INSERT INTO chat (id) VALUES (12345)';
        $statement = $this->pdo->prepare($insert_test_chat);
        $statement->execute();

        // Load fixtures into the database for the tests
        $this->em = $this->getDoctrineEntityManager();
        $this->xu = new XmlUtils($this->em);

        $loader = new Loader();
        $loader->addFixture(new UserDataLoader());
        $loader->addFixture(new GanttProjectDataLoader());

        $purger = new ORMPurger();
        $executor = new ORMExecutor($this->em, $purger);
        $executor->execute($loader->getFixtures());

        $this->user = $this->em->getRepository(User::class)->find(12345);
        $this->ganttProject = $this->user->getGanttProjects()[0];
    }

    public function tearDown(): void
    {
        $connection = $this->em->getConnection();
        $platform = $connection->getDatabasePlatform();

        $connection->executeQuery('SET FOREIGN_KEY_CHECKS = 0;');

        $truncate = $platform->getTruncateTableSQL('chat');
        $connection->executeUpdate($truncate);

        $truncate = $platform->getTruncateTableSQL('user');
        $connection->executeUpdate($truncate);

        $truncate = $platform->getTruncateTableSQL('ganttproject');
        $connection->executeUpdate($truncate);

        $truncate = $platform->getTruncateTableSQL('task');
        $connection->executeUpdate($truncate);

        $connection->executeQuery('SET FOREIGN_KEY_CHECKS = 1;');
    }

    /**
     * @return PHPUnit\DbUnit\DataSet\IDataSet
     */
    public function getDataSet()
    {
        return $this->createXmlDataSet($this->ganDir.'milestoneSeed.xml');
    }

    public function testInsertTwelveTasksDb()
    {
        $telegram = new Telegram('123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11', 'TestO');
        $telegram->enableExternalMySql($this->pdo);

        $this->xu->extractStoreTasks(
            $this->ganDir.'TwelveTasks.gan',
            12345,
            $this->ganttProject
        );

        // Get all the tasks and check if there are a correct amount of them
        $tasks = $this->em->getRepository(Task::class)->findAll();
        $this->assertSame(12, \count($tasks));

        // Load the expected tasks and compare them with the database tasks
        $expectedTasks = Yaml::parseFile($this->dataDir.'expectedTasks.yaml');
        foreach ($tasks as $i => $task) {
            $this->assertEquals($expectedTasks[$i]['gan_id'], $task->getGanId());
            $this->assertEquals($expectedTasks[$i]['chat_id'], $task->getChat_id());
            $this->assertEquals($expectedTasks[$i]['task_name'], $task->getName());
            $this->assertEquals($expectedTasks[$i]['task_date'], $task->getDate()->format('Y-m-d'));
            $this->assertEquals($expectedTasks[$i]['task_isMilestone'], $task->getIsMilestone());
            $this->assertEquals($expectedTasks[$i]['task_duration'], $task->getDuration());
        }
    }

    public function testInsertTwelveTasksWithNoNameDb()
    {
        $telegram = new Telegram('123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11', 'TestO');
        $telegram->enableExternalMySql($this->pdo);

        $this->xu->extractStoreTasks(
            $this->ganDir.'TwelveTasksNoName.gan',
            12345,
            $this->ganttProject
        );

        // Get all the tasks and check if there are a correct amount of them
        $tasks = $this->em->getRepository(Task::class)->findAll();
        $this->assertSame(12, \count($tasks));

        // Load the expected tasks and compare them with the database tasks
        $expectedTasks = Yaml::parseFile($this->dataDir.'expectedTasks.yaml');
        foreach ($tasks as $i => $task) {
            $this->assertEquals($expectedTasks[$i]['gan_id'], $task->getGanId());
            $this->assertEquals($expectedTasks[$i]['chat_id'], $task->getChat_id());
            $this->assertEquals('', $task->getName());
            $this->assertEquals($expectedTasks[$i]['task_date'], $task->getDate()->format('Y-m-d'));
            $this->assertEquals($expectedTasks[$i]['task_isMilestone'], $task->getIsMilestone());
            $this->assertEquals($expectedTasks[$i]['task_duration'], $task->getDuration());
        }
    }

    public function testExtractTasksEmptyException()
    {
        $this->expectException(NoTasksException::class);

        $this->xu->extractStoreTasks(
            $this->ganDir.'NoTasks.gan',
            12345,
            $this->ganttProject
        );
    }

    public function testfindOneNestedTask()
    {
        // Load the fixtures
        $this->xu->extractStoreTasks(
            $this->ganDir.'TwelveTasks.gan',
            12345,
            $this->ganttProject
        );

        $xml = $this->xu->openXmlFile($this->ganDir.'TwelveTasks.gan');

        // Get the XML task which has one nested task
        $xmlTask = $xml->xpath('//task[@id="4"]')[0];

        $taskPool = [];
        $this->xu->findNestedTaskOrDepend($taskPool, $this->ganttProject, $xmlTask, true);

        $this->assertEquals(1, \count($taskPool));

        $task = $taskPool[0];

        $this->assertEquals(7, $task->getGanId());
        $this->assertEquals('Task group', $task->getName());
        $this->assertEquals(false, $task->getIsMilestone());
        $this->assertEquals('2021-05-20', $task->getDate()->format('Y-m-d'));
        $this->assertEquals(3, $task->getDuration());
    }

    public function testfindTwoNestedTasks()
    {
        // Load the fixtures
        $this->xu->extractStoreTasks(
            $this->ganDir.'TwelveTasks.gan',
            12345,
            $this->ganttProject
        );

        /**
         * The task below is modified and then the same file is imported again.
         * Then, a new GanttProject is created and the same tasks are imported
         * into the database. Without the GanttProject variable in the
         * findNestedTaskOrDepend function, the performed search by ganId would
         * retrieve this modified task, and therefore making the test fail.
         *
         * This little hack is to justify searching not only by ganId, but also
         * by GanttProject to retrieve the correct task.
         */
        $task = $this->em->getRepository(Task::class)->find(7);
        $task->setDate(new \DateTime('2021-05-10'));
        $this->em->persist($task);
        $this->em->flush();

        $ganttProject = new GanttProject();
        $ganttProject->setFileName('TwelveTasks.gan');
        $ganttProject->setVersion(2);
        $ganttProject->setUser($this->user);

        $this->em->persist($ganttProject);
        $this->em->flush();

        // Load the fixtures
        $this->xu->extractStoreTasks(
            $this->ganDir.'TwelveTasks.gan',
            12345,
            $ganttProject
        );

        $xml = $this->xu->openXmlFile($this->ganDir.'TwelveTasks.gan');

        // Get the XML task which has two nested tasks
        $xmlTask = $xml->xpath('//task[@id="7"]')[0];

        $taskPool = [];
        $this->xu->findNestedTaskOrDepend($taskPool, $ganttProject, $xmlTask, true);

        $this->assertEquals(2, \count($taskPool));

        $task = $taskPool[0];

        $this->assertEquals(24, $task->getGanId());
        $this->assertEquals('Third task', $task->getName());
        $this->assertEquals(false, $task->getIsMilestone());
        $this->assertEquals('2021-05-20', $task->getDate()->format('Y-m-d'));
        $this->assertEquals(3, $task->getDuration());

        $task = $taskPool[1];

        $this->assertEquals(8, $task->getGanId());
        $this->assertEquals('Third milestone', $task->getName());
        $this->assertEquals(true, $task->getIsMilestone());
        $this->assertEquals('2021-05-21', $task->getDate()->format('Y-m-d'));
        $this->assertEquals(0, $task->getDuration());
    }

    public function testfindNestedDepend()
    {
        // Load the fixtures
        $this->xu->extractStoreTasks(
            $this->ganDir.'TwelveTasks.gan',
            12345,
            $this->ganttProject
        );

        $xml = $this->xu->openXmlFile($this->ganDir.'TwelveTasks.gan');

        // Get the XML task which has two nested tasks
        $xmlTask = $xml->xpath('//task[@id="14"]')[0];

        $taskPool = [];
        $this->xu->findNestedTaskOrDepend($taskPool, $this->ganttProject, $xmlTask, false);

        $this->assertEquals(1, \count($taskPool));

        $task = $taskPool[0];

        $this->assertEquals(0, $task->getGanId());
        $this->assertEquals('First milestone', $task->getName());
        $this->assertEquals(true, $task->getIsMilestone());
        $this->assertEquals('2021-05-18', $task->getDate()->format('Y-m-d'));
        $this->assertEquals(0, $task->getDuration());
        $this->assertSame($this->ganttProject, $task->getGanttProject());
    }

    public function testModifyDurationTaskOneDependency()
    {
        // Load the fixtures
        $this->xu->extractStoreTasks(
            $this->ganDir.'TwelveTasks.gan',
            12345,
            $this->ganttProject
        );

        $task = $this->em->getRepository(Task::class)->findOneBy(
            [
                'ganId' => 14,
            ]
        );

        // Delay the task three days
        $resultingXml = $this->xu->delayTaskAndDependants(
            $this->ganDir.'TwelveTasks.gan',
            $task,
            3
        );

        // Get all the tasks and check if there are a correct amount of them
        $tasks = $this->em->getRepository(Task::class)->findAll();
        $this->assertSame(12, \count($tasks));

        // Load the expected tasks and compare them with the database tasks
        $expectedTasks = Yaml::parseFile($this->dataDir.'expectedTasksWithModifiedDateOneDependency.yaml');
        foreach ($tasks as $i => $task) {
            $this->assertEquals($expectedTasks[$i]['gan_id'], $task->getGanId());
            $this->assertEquals($expectedTasks[$i]['chat_id'], $task->getChat_id());
            $this->assertEquals($expectedTasks[$i]['task_name'], $task->getName());
            $this->assertEquals($expectedTasks[$i]['task_date'], $task->getDate()->format('Y-m-d'));
            $this->assertEquals($expectedTasks[$i]['task_isMilestone'], $task->getIsMilestone());
            $this->assertEquals($expectedTasks[$i]['task_duration'], $task->getDuration());
        }

        // Check that the XML was properly udpated
        $xmlTask = $resultingXml->xpath('//task[@id="14"]')[0];
        $this->assertEquals('2021-05-11', $xmlTask->attributes()->start);
        $this->assertEquals('8', $xmlTask[0]->attributes()->duration);

        $xmlTask = $resultingXml->xpath('//task[@id="0"]')[0];
        $this->assertEquals('2021-05-21', $xmlTask->attributes()->start);
    }

    public function testModifyDurationTaskAndDependenciesManyDependencies()
    {
        // Load the fixtures
        $this->xu->extractStoreTasks(
            $this->ganDir.'TwelveTasks.gan',
            12345,
            $this->ganttProject
        );

        $task = $this->em->getRepository(Task::class)->findOneBy(
            [
                'ganId' => 4,
            ]
        );

        // Delay the task three days
        $resultingXml = $this->xu->delayTaskAndDependants(
            $this->ganDir.'TwelveTasks.gan',
            $task,
            3
        );

        // Get all the tasks and check if there are a correct amount of them
        $tasks = $this->em->getRepository(Task::class)->findAll();
        $this->assertSame(12, \count($tasks));

        // Load the expected tasks and compare them with the database tasks
        $expectedTasks = Yaml::parseFile($this->dataDir.'expectedTasksWithModifiedDateManyDependencies.yaml');
        foreach ($tasks as $i => $task) {
            $this->assertEquals($expectedTasks[$i]['gan_id'], $task->getGanId());
            $this->assertEquals($expectedTasks[$i]['chat_id'], $task->getChat_id());
            $this->assertEquals($expectedTasks[$i]['task_name'], $task->getName());
            $this->assertEquals($expectedTasks[$i]['task_date'], $task->getDate()->format('Y-m-d'));
            $this->assertEquals($expectedTasks[$i]['task_isMilestone'], $task->getIsMilestone());
            $this->assertEquals($expectedTasks[$i]['task_duration'], $task->getDuration());
        }

        // Check that the XML was properly udpated
        $xmlTask = $resultingXml->xpath('//task[@id="4"]')[0];
        $this->assertEquals('2021-05-20', $xmlTask->attributes()->start);
        $this->assertEquals('6', $xmlTask[0]->attributes()->duration);

        $xmlTask = $resultingXml->xpath('//task[@id="7"]')[0];
        $this->assertEquals('2021-05-23', $xmlTask->attributes()->start);

        $xmlTask = $resultingXml->xpath('//task[@id="24"]')[0];
        $this->assertEquals('2021-05-23', $xmlTask->attributes()->start);

        $xmlTask = $resultingXml->xpath('//task[@id="8"]')[0];
        $this->assertEquals('2021-05-24', $xmlTask->attributes()->start);

        $xmlTask = $resultingXml->xpath('//task[@id="17"]')[0];
        $this->assertEquals('2021-05-24', $xmlTask->attributes()->start);
    }
}
