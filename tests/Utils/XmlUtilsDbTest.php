<?php

declare(strict_types=1);

namespace IkastenBot\Tests\Utils;

use IkastenBot\Utils\XmlUtils;
use IkastenBot\Tests\DatabaseTestCase;
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

    public function setUp(): void
    {
        $this->data_dir    = __DIR__ . '/../_data/xml_milestone_data';
        $this->xml_dir_gan  = $this->data_dir . '/gan/';
        $this->xu = new XmlUtils();

        $this->connection = $this->getConnection();
        $this->pdo = $this->connection->getConnection();
        $this->pdo->beginTransaction();

        $insert_test_chat = 'INSERT INTO chat (id) VALUES (12345)';
        $statement = $this->pdo->prepare($insert_test_chat);
        $statement->execute();
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
        return $this->createXmlDataSet($this->xml_dir_gan . 'milestoneSeed.xml');
    }

    public function testInsertFiveMilestonesDb()
    {
        $telegram = new Telegram('123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11', 'TestO');
        $telegram->enableExternalMySql($this->pdo);

        $this->xu->extractStoreMilestones(
            $this->xml_dir_gan . 'FiveMilestones.gan',
            12345
        );

        $queryTable = $this->connection->createQueryTable(
            'milestone', 'SELECT chat_id, milestone_name, milestone_date FROM milestone'
        );

        $expectedTable = $this->createFlatXmlDataSet(dirname(__FILE__).'/../_data/xml_milestone_data/expectedMilestones.xml')
                                ->getTable('milestone');

        $this->assertTablesEqual($expectedTable, $queryTable);
        $this->assertSame(5, $this->connection->getRowCount('milestone'));
    }

    public function testInsertFiveMilestonesWithNoNameDb()
    {
        $telegram = new Telegram('123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11', 'TestO');
        $telegram->enableExternalMySql($this->pdo);

        $this->xu->extractStoreMilestones(
            $this->xml_dir_gan . 'FiveMilestonesNoName.gan',
            12345
        );

        $queryTable = $this->connection->createQueryTable(
            'milestone', 'SELECT chat_id, milestone_name, milestone_date FROM milestone'
        );

        $expectedTable = $this->createXmlDataSet(dirname(__FILE__).'/../_data/xml_milestone_data/expectedMilestonesWithNoName.xml')
                                ->getTable('milestone');

        $this->assertTablesEqual($expectedTable, $queryTable);
        $this->assertSame(5, $this->connection->getRowCount('milestone'));
    }
}
