<?php

namespace IkastenBot\Entity;

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Longman\TelegramBot\DB;

/**
 * Helper for managing the database connections and Doctrine's entity manager
 */
class DoctrineBootstrap
{
    /**
     * Entity Manager
     *
     * @var EntityManager
     */
    protected $em;

    /**
     * Create an entity manager. If there is an active PDO connection then it
     * creates it from there.
     *
     * @return void
     */
    private function createEntityManager(): void
    {
        $config = Setup::createAnnotationMetadataConfiguration(array("."), false);

        $activePdo = DB::getPdo();
        if (\is_null($activePdo)) {
            $connectionParams = array(
                'driver'   => 'pdo_mysql',
                'user'     => getenv('MYSQL_USERNAME'),
                'password' => getenv('MYSQL_USER_PASSWORD'),
                'dbname'   => getenv('MYSQL_DATABASE_NAME'),
            );
        } else {
            $connectionParams = [
                'driver' => 'pdo_mysql',
                'pdo' => DB::getPdo()
            ];
        }

        $this->em = EntityManager::create($connectionParams, $config);
    }

    /**
     * Return the entity manager
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
}
