<?php

declare(strict_types=1);

namespace IkastenBot\Tests\Utils;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use IkastenBot\Entity\Task;
use IkastenBot\Entity\User;
use IkastenBot\Tests\DatabaseTestCase;
use IkastenBot\Tests\Fixtures\GanttProjectDataLoader;
use IkastenBot\Tests\Fixtures\SingleTaskDataLoader;
use IkastenBot\Tests\Fixtures\UserDataLoader;
use IkastenBot\Utils\TaskUtils;
use Longman\TelegramBot\Telegram;

/**
 * @internal
 * @coversNothing
 */
final class TaskUtilsDbTest extends DatabaseTestCase
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * Test task.
     *
     * @var Task
     */
    protected $task;
    /**
     * Directory path containing test files.
     *
     * @var string
     */
    private $dataDir;

    /**
     * Task Utils.
     *
     * @var TaskUtils
     */
    private $tu;

    /**
     * Database connection.
     *
     * @var PHPUnit\DbUnit\Database\Connection
     */
    private $connection;

    /**
     * PDO object.
     *
     * @var PDO
     */
    private $pdo;

    public function setUp(): void
    {
        $this->dataDir = __DIR__.'/../_data/task_data/';

        $this->connection = $this->getConnection();
        $this->pdo = $this->connection->getConnection();
        // Insert a test chat to avoid the foreign key constraints
        $insert_test_chat = 'INSERT INTO chat (id) VALUES (12345)';
        $statement = $this->pdo->prepare($insert_test_chat);
        $statement->execute();

        $this->em = $this->getDoctrineEntityManager();

        // Load fixtures into the database for the tests
        $loader = new Loader();
        $loader->addFixture(new UserDataLoader());
        $loader->addFixture(new GanttProjectDataLoader());
        $loader->addFixture(new SingleTaskDataLoader());

        $purger = new ORMPurger();
        $executor = new ORMExecutor($this->em, $purger);
        $executor->execute($loader->getFixtures());

        $this->user = $this->em->getRepository(User::class)->find(12345);
        $this->ganttProject = $this->user->getGanttProjects()[0];

        // Create a fake telegram bot
        $telegram = new Telegram('123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11', 'TestO');
        $telegram->enableExternalMySql($this->pdo);

        // Fetch the test task
        $this->task = $this->em->getRepository(Task::class)->findOneBy(
            ['chat_id' => '12345']
        );

        $this->tu = new TaskUtils($this->em);
    }

    public function tearDown(): void
    {
        $connection = $this->em->getConnection();
        $platform = $connection->getDatabasePlatform();

        $connection->executeQuery('SET FOREIGN_KEY_CHECKS = 0');

        $truncate = $platform->getTruncateTableSQL('chat');
        $connection->executeUpdate($truncate);

        $truncate = $platform->getTruncateTableSQL('user');
        $connection->executeUpdate($truncate);

        $truncate = $platform->getTruncateTableSQL('ganttproject');
        $connection->executeUpdate($truncate);

        $truncate = $platform->getTruncateTableSQL('task');
        $connection->executeUpdate($truncate);

        $connection->executeQuery('SET FOREIGN_KEY_CHECKS = 1');
    }

    /**
     * @return PHPUnit\DbUnit\DataSet\IDataSet
     */
    public function getDataSet()
    {
        return $this->createXmlDataSet($this->dataDir.'taskSeed.xml');
    }

    public function testModifyTaskDurationPositiveOffset()
    {
        $this->tu->modifyTaskDuration($this->task, 5);

        $queryTable = $this->connection->createQueryTable(
            'task',
            'SELECT
                `gan_id`,
                `chat_id`,
                `task_name`,
                `task_date`,
                `task_isMilestone`,
                `task_duration`
            FROM `task`'
        );

        $expectedTable = $this->createFlatXmlDataSet($this->dataDir.'expectedTaskPositiveOffset.xml')
            ->getTable('task')
        ;

        $this->assertTablesEqual($expectedTable, $queryTable);
        $this->assertSame(1, $this->connection->getRowCount('task'));
    }

    public function testModifyTaskDurationNegativeOffset()
    {
        $this->tu->modifyTaskDuration($this->task, -1);

        $queryTable = $this->connection->createQueryTable(
            'task',
            'SELECT
                `gan_id`,
                `chat_id`,
                `task_name`,
                `task_date`,
                `task_isMilestone`,
                `task_duration`
            FROM `task`'
        );

        $expectedTable = $this->createFlatXmlDataSet($this->dataDir.'expectedTaskNegativeOffset.xml')
            ->getTable('task')
        ;

        $this->assertTablesEqual($expectedTable, $queryTable);
        $this->assertSame(1, $this->connection->getRowCount('task'));
    }
}
