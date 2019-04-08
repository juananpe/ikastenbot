<?php

declare(strict_types=1);

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class DatabaseTestCase extends KernelTestCase
{
    /**
     * Doctrine's entity manager.
     *
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    /**
     * Returns an entity manager.
     */
    public function getEntityManager()
    {
        if (\is_null($this->entityManager)) {
            $kernel = self::bootKernel();
            $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        }

        return $this->entityManager;
    }

    /**
     * Closes entity manager to avoid memory leaks.
     */
    public function closeEntityManager()
    {
        $this->entityManager->close();
        $this->entityManager = null;
    }

    /**
     * Truncates the given tables from the database.
     *
     * @param array $tables Table names e.g. ['a', 'b', 'c']
     */
    public function truncateTables(array $tables)
    {
        $connection = $this->entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();

        // Disable foreign key checks to avoid errors
        $connection->executeQuery('SET FOREIGN_KEY_CHECKS = 0');

        foreach ($tables as $table) {
            $truncate = $platform->getTruncateTableSQL($table);
            $connection->executeUpdate($truncate);
        }

        // Reenable foreign key checks to previous state
        $connection->executeQuery('SET FOREIGN_KEY_CHECKS = 1');
    }

    /**
     * Insert a dummy test chat into the database. Often required to insert
     * other data.
     *
     * @param int $id The id fallbacks to the '12345' constant if not
     *                specified
     */
    public function insertDummyTestChat()
    {
        $pdo = $this->entityManager->getConnection()->getWrappedConnection();

        $insert = 'INSERT INTO `chat` (`id`) VALUES (12345)';
        $statement = $pdo->prepare($insert);
        $statement->execute();
    }
}
