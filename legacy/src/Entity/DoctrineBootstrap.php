<?php

namespace IkastenBot\Entity;

use Doctrine\Common\Cache\ApcCache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Longman\TelegramBot\DB;

/**
 * Helper for managing the database connections and Doctrine's entity manager.
 */
class DoctrineBootstrap
{
    /**
     * Entity Manager.
     *
     * @var EntityManager
     */
    protected $em;

    /**
     * Return the entity manager.
     *
     * @return EntityManager
     */
    public function getEntityManager(): EntityManager
    {
        if (\is_null($this->em)) {
            $this->createEntityManager();
        }

        return $this->em;
    }

    /**
     * Create an entity manager. If there is an active PDO connection then it
     * creates it from there. Check the link for the configuration reference.
     *
     * @see https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/reference/advanced-configuration.html#advanced-configuration
     */
    private function createEntityManager(): void
    {
        $devMode = !\array_key_exists('TBGP_ENV', $_SERVER);

        if ($devMode) {
            $cache = new ArrayCache();
        } else {
            $cache = new ApcCache();
        }

        $config = new Configuration();
        $config->setMetadataCacheImpl($cache);
        $driverImpl = $config->newDefaultAnnotationDriver(PROJECT_ROOT.'/src/Entity');
        $config->setMetadataDriverImpl($driverImpl);
        $config->setQueryCacheImpl($cache);
        $config->setProxyDir(PROJECT_ROOT.'/var/cache/Doctrine/proxies');
        $config->setProxyNamespace('IkastenBot\Proxies');
        $config->setAutoGenerateProxyClasses($devMode);

        $activePdo = DB::getPdo();
        if (\is_null($activePdo)) {
            $connectionParams = [
                'driver' => 'pdo_mysql',
                'user' => getenv('MYSQL_USERNAME'),
                'password' => getenv('MYSQL_USER_PASSWORD'),
                'dbname' => getenv('MYSQL_DATABASE_NAME'),
                'host' => getenv('MYSQL_HOST'),
            ];
        } else {
            $connectionParams = [
                'driver' => 'pdo_mysql',
                'pdo' => DB::getPdo(),
            ];
        }

        $this->em = EntityManager::create($connectionParams, $config);
    }
}
