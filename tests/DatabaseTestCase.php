<?php

declare(strict_types=1);

namespace TelegramBotGanttProject\Tests;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use PHPUnit\DbUnit\TestCaseTrait;
use PHPUnit\Framework\TestCase;

abstract class DatabaseTestCase extends TestCase
{
    use TestCaseTrait;

    // only instantiate pdo once for test clean-up/fixture load
    static private $pdo = null;

    // only instantiate PHPUnit\DbUnit\Database\Connection once per test
    private $conn = null;

    // only instantiate doctrine once for test clean-up/fixture load
    static private $em = null;

    /**
     * @return PHPUnit\DbUnit\Database\Connection
     */
    public function getConnection()
    {
        if ($this->conn === null) {
            if (self::$pdo == null) {
                self::$pdo = new \PDO(
                    'mysql:dbname=' . $GLOBALS['MYSQL_TEST_DATABASE_NAME'] . ';host=' . $GLOBALS['MYSQL_TEST_HOST'],
                    $GLOBALS['MYSQL_TEST_USER'],
                    $GLOBALS['MYSQL_TEST_PASSWORD']
                    );
            }
            $this->conn = $this->createDefaultDBConnection(self::$pdo, ':memory:');
        }

        return $this->conn;
    }

    public function getDoctrineEntityManager()
    {
        if (self::$em === null) {
            $config = Setup::createAnnotationMetadataConfiguration(array(__DIR__."/../Entity/"), false);

            $connectionParams = array(
                'pdo' => self::$pdo
            );

            self::$em = EntityManager::create($connectionParams, $config);
        }

        return self::$em;
    }

    public function getDataSet()
    {
    }
}
