<?php

declare(strict_types=1);

namespace IkastenBot\Tests\Utils;

use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use IkastenBot\Entity\GanttProject;
use IkastenBot\Entity\User;
use IkastenBot\Exception\NoTasksException;
use IkastenBot\Utils\XmlUtils;
use IkastenBot\Tests\DatabaseTestCase;
use IkastenBot\Tests\Fixtures\GanttProjectDataLoader;
use IkastenBot\Tests\Fixtures\UserDataLoader;
use Longman\TelegramBot\Telegram;
use PHPUnit\Framework\TestCase;

final class XmlUtilsDbTest extends DatabaseTestCase
{
    /**
     * Directory path containing test files
     *
     * @var string
     */
    private $data_dir;

    /**
     * Directory containing Gan XML files to be imported
     *
     * @var string
     */
    private $xml_dir_gan;

    /**
     * XML Utils
     *
     * @var XmlUtils
     */
    private $xu;

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

    /**
     * Entity manager
     *
     * @var Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * Test user
     *
     * @var User
     */
    protected $user;

    /**
     * Test GanttProject
     *
     * @var GanttProject
     */
    protected $ganttProject;

    public function setUp(): void
    {
        $this->data_dir    = __DIR__ . '/../_data/xml_milestone_data';
        $this->xml_dir_gan  = $this->data_dir . '/gan/';

        $this->connection = $this->getConnection();
        $this->pdo = $this->connection->getConnection();

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
        $platform   = $connection->getDatabasePlatform();

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
        return $this->createXmlDataSet($this->xml_dir_gan . 'milestoneSeed.xml');
    }

    public function testInsertTwelveTasksDb()
    {
        $telegram = new Telegram('123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11', 'TestO');
        $telegram->enableExternalMySql($this->pdo);

        $this->xu->extractStoreTasks(
            $this->xml_dir_gan . 'TwelveTasks.gan',
            12345,
            $this->ganttProject
        );

        $queryTable = $this->connection->createQueryTable(
            'task', 'SELECT gan_id, chat_id, task_name, task_date, task_isMilestone, task_duration FROM task'
        );

        $expectedTable = $this->createFlatXmlDataSet(dirname(__FILE__).'/../_data/xml_task_data/expectedTasks.xml')
                                ->getTable('task');

        $this->assertTablesEqual($expectedTable, $queryTable);
        $this->assertSame(12, $this->connection->getRowCount('task'));
    }

    public function testInsertTwelveTasksWithNoNameDb()
    {
        $telegram = new Telegram('123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11', 'TestO');
        $telegram->enableExternalMySql($this->pdo);

        $this->xu->extractStoreTasks(
            $this->xml_dir_gan . 'TwelveTasksNoName.gan',
            12345,
            $this->ganttProject
        );

        $queryTable = $this->connection->createQueryTable(
            'task', 'SELECT gan_id, chat_id, task_name, task_date, task_isMilestone, task_duration FROM task'
        );

        $expectedTable = $this->createXmlDataSet(dirname(__FILE__).'/../_data/xml_task_data/expectedTasksWithNoName.xml')
                                ->getTable('task');

        $this->assertTablesEqual($expectedTable, $queryTable);
        $this->assertSame(12, $this->connection->getRowCount('task'));
    }

    public function testExtractTasksEmptyException()
    {
        $this->expectException(NoTasksException::class);

        $this->xu->extractStoreTasks(
            $this->xml_dir_gan . 'NoTasks.gan',
            12345,
            $this->ganttProject
        );
    }
}
