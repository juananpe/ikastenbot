<?php

declare(strict_types=1);

namespace IkastenBot\Tests;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use PHPUnit\DbUnit\TestCaseTrait;
use PHPUnit\Framework\TestCase;

abstract class DatabaseTestCase extends TestCase
{
    use TestCaseTrait;

    // only instantiate pdo once for test clean-up/fixture load
    private static $pdo = null;

    // only instantiate PHPUnit\DbUnit\Database\Connection once per test
    private $conn;

    // only instantiate doctrine once for test clean-up/fixture load
    private static $em = null;

    /**
     * @return PHPUnit\DbUnit\Database\Connection
     */
    public function getConnection()
    {
        if (null === $this->conn) {
            if (null == self::$pdo) {
                self::$pdo = new \PDO(
                    'mysql:dbname='.$GLOBALS['MYSQL_TEST_DATABASE_NAME'].';host='.$GLOBALS['MYSQL_TEST_HOST'],
                    $GLOBALS['MYSQL_TEST_USER'],
                    $GLOBALS['MYSQL_TEST_PASSWORD']
                    );
            }
            $this->conn = $this->createDefaultDBConnection(self::$pdo, ':memory:');
        }

        return $this->conn;
    }

    /**
     * Check the link for the configuration reference.
     *
     * @see https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/reference/advanced-configuration.html#advanced-configuration
     */
    public function getDoctrineEntityManager()
    {
        if (null === self::$em) {
            $cache = new ArrayCache();

            $config = new Configuration();
            $config->setMetadataCacheImpl($cache);
            $driverImpl = $config->newDefaultAnnotationDriver(['src/Entity']);
            $config->setMetadataDriverImpl($driverImpl);
            $config->setQueryCacheImpl($cache);
            $config->setProxyDir('var/cache/Doctrine/proxies');
            $config->setProxyNamespace('IkastenBot\Proxies');
            $config->setAutoGenerateProxyClasses(true);
            $config = Setup::createAnnotationMetadataConfiguration(['src/Entity'], true);

            $connectionParams = [
                'pdo' => self::$pdo,
            ];

            self::$em = EntityManager::create($connectionParams, $config);
        }

        return self::$em;
    }

    public function getDataSet()
    {
    }
}
