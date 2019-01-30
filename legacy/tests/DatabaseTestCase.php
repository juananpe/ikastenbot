<?php

declare(strict_types=1);

namespace IkastenBot\Tests;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;

abstract class DatabaseTestCase extends TestCase
{
    // only instantiate doctrine once for test clean-up/fixture load
    private $em;

    /**
     * Check the link for the configuration reference.
     *
     * @see https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/reference/advanced-configuration.html#advanced-configuration
     */
    public function getDoctrineEntityManager()
    {
        if (null === $this->em) {
            $cache = new ArrayCache();

            $config = new Configuration();
            $config->setMetadataCacheImpl($cache);
            $driverImpl = $config->newDefaultAnnotationDriver(['src/Entity']);
            $config->setMetadataDriverImpl($driverImpl);
            $config->setQueryCacheImpl($cache);
            $config->setProxyDir('var/cache/Doctrine/proxies');
            $config->setProxyNamespace('IkastenBot\Proxies');
            $config->setAutoGenerateProxyClasses(true);

            $connectionParams = [
                'driver' => 'pdo_mysql',
                'user' => $GLOBALS['MYSQL_TEST_USER'],
                'password' => $GLOBALS['MYSQL_TEST_PASSWORD'],
                'dbname' => $GLOBALS['MYSQL_TEST_DATABASE_NAME'],
                'host' => $GLOBALS['MYSQL_TEST_HOST'],
            ];

            $this->em = EntityManager::create($connectionParams, $config);
        }

        return $this->em;
    }
}
