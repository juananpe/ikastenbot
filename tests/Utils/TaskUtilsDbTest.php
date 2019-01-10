<?php

declare(strict_types=1);

namespace IkastenBot\Tests\Utils;

use Doctrine\ORM\EntityManager;
use IkastenBot\Exception\NoTasksException;
use IkastenBot\Exception\TaskNotFoundException;
use IkastenBot\Tests\DatabaseTestCase;
use IkastenBot\Utils\TaskUtils;
use Longman\TelegramBot\Telegram;
use PHPUnit\Framework\TestCase;

final class TaskUtilsDbTest extends DatabaseTestCase
{
    /**
     * Directory path containing test files
     *
     * @var string
     */
    private $data_dir;

    /**
     * Task Utils
     *
     * @var TaskUtils
     */
    private $tu;

    /**
     * Database connection
     *
     * @var PHPUnit\DbUnit\Database\Connection
     */
    private $connection;

    /**
     * PDO object
     *
     * @var PDO
     */
    private $pdo;

    /**
     * @var \UnitTester
     */
    protected $tester;

    public function setUp(): void
    {
        $this->dataDir = __DIR__ . '/../_data/task_data/';

        $this->connection = $this->getConnection();
        $this->pdo = $this->connection->getConnection();
        $this->pdo->beginTransaction();

        // Insert a test chat to avoid the foreign key constraints
        $insert_test_chat = 'INSERT INTO chat (id) VALUES (12345)';
        $statement = $this->pdo->prepare($insert_test_chat);
        $statement->execute();

            // Insert a test user to avoid triggering foreign key constraints
            $insertTestUser = '
            INSERT INTO `user` (
                `id`,
                `is_bot`,
                `first_name`,
                `last_name`,
                `username`,
                `language_code`,
                `created_at`,
                `updated_at`,
                `language`
            ) VALUES (
                12345,
                0,
                "Test",
                "User",
                "TestUsername",
                "en",
                "2021-01-01 00:00:00",
                "2021-01-01 00:00:00",
                "es"
            )
        ';

        $statement = $this->pdo->prepare($insertTestUser);
        $statement->execute();

        // Insert a test GanttProject to avoid triggering foreign key constraints
        $insertTestGanttProject = '
            INSERT INTO `ganttproject` (
                `id`,
                `file_name`,
                `version`,
                `user_id`
            ) VALUES (
                1,
                "Test.gan",
                1,
                12345
            )'
        ;

        $statement = $this->pdo->prepare($insertTestGanttProject);
        $statement->execute();

        // Insert a test task to test with
        $insert_test_task = '
            INSERT INTO `task` (
                `id`,
                `chat_id`,
                `task_name`,
                `task_date`,
                `task_isMilestone`,
                `task_duration`,
                `ganttproject_id`
            ) VALUES (
                1,
                12345,
                "Test task",
                "2021-01-01 00:00:00",
                0,
                3,
                1
            )
        ';
        $statement = $this->pdo->prepare($insert_test_task);
        $statement->execute();

        // Create a fake telegram bot
        $telegram = new Telegram('123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11', 'TestO');
        $telegram->enableExternalMySql($this->pdo);

        /**
         * In order to mimic the conditions in which TaskUtils will be used,
         * no entity manager is passed to the constructor. For this reason
         * TaskUtils is created after the fake Telegram bot —this way it reuses
         * the existing PDO connection—
         */
        // TaskUtils needs to be created after the Telegram bot in order t
        $this->tu = new TaskUtils();
    }

    public function tearDown(): void
    {
        $this->pdo->rollBack();
    }

    /**
     * @return PHPUnit\DbUnit\DataSet\IDataSet
     */
    public function getDataSet()
    {
        return $this->createXmlDataSet($this->dataDir . 'taskSeed.xml');
    }

    public function testModifyTaskDurationPositiveOffset()
    {
        $this->tu->modifyTaskDuration(
            1,
            5
        );

        $queryTable = $this->connection->createQueryTable(
            'task',
            'SELECT
                `id`,
                `chat_id`,
                `task_name`,
                `task_date`,
                `task_isMilestone`,
                `task_duration`
            FROM `task`'
        );

        $expectedTable = $this->createFlatXmlDataSet($this->dataDir . 'expectedTaskPositiveOffset.xml')
                                ->getTable('task');

        $this->assertTablesEqual($expectedTable, $queryTable);
        $this->assertSame(1, $this->connection->getRowCount('task'));
    }

    public function testModifyTaskDurationNegativeOffset()
    {
        $this->tu->modifyTaskDuration(
            1,
            -1
        );

        $queryTable = $this->connection->createQueryTable(
            'task',
            'SELECT
                `id`,
                `chat_id`,
                `task_name`,
                `task_date`,
                `task_isMilestone`,
                `task_duration`
            FROM `task`'
        );

        $expectedTable = $this->createFlatXmlDataSet($this->dataDir . 'expectedTaskNegativeOffset.xml')
                                ->getTable('task');

        $this->assertTablesEqual($expectedTable, $queryTable);
        $this->assertSame(1, $this->connection->getRowCount('task'));
    }

    public function testTaskNotFoundException()
    {
        $this->expectException(TaskNotFoundException::class);

        $this->tu->modifyTaskDuration(
            12345,
            1
        );
    }
}
