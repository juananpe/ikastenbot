<?php

declare(strict_types=1);

use IkastenBot\Entity\GanttProject;
use IkastenBot\Entity\User;
use IkastenBot\Tests\DatabaseTestCase;

/**
 * @internal
 * @coversNothing
 */
class GanttProjectRepositoryTest extends DatabaseTestCase
{
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

    /**
     * Doctrine entity manager.
     *
     * @var Doctrine\ORM\EntityManager
     */
    private $dem;

    /**
     * The created test user.
     *
     * @var User
     */
    private $user;

    public function setUp(): void
    {
        $this->connection = $this->getConnection();
        $this->pdo = $this->connection->getConnection();
        $this->dem = $this->getDoctrineEntityManager();

        $fixedDate = new \DateTime('2021-01-01');

        $this->user = new User();
        $this->user->setId(12345);
        $this->user->setBot(false);
        $this->user->setFirstName('Test');
        $this->user->setLastName('Test');
        $this->user->setUsername('Test');
        $this->user->setLanguageCode('en');
        $this->user->setCreatedAt($fixedDate);
        $this->user->setUpdatedAt($fixedDate);
        $this->user->setLanguage('en');

        $this->dem->persist($this->user);
        $this->dem->flush();

        for ($i = 0; $i < 3; ++$i) {
            $gp = new GanttProject();
            $gp->setFilename('TestFilename.gan');
            $gp->setVersion($i + 1);

            $this->user->addGanttProject($gp);
        }

        $this->dem->persist($this->user);
        $this->dem->flush();
    }

    public function tearDown(): void
    {
        $connection = $this->dem->getConnection();
        $platform = $connection->getDatabasePlatform();

        $connection->executeQuery('SET FOREIGN_KEY_CHECKS = 0');

        $truncate = $platform->getTruncateTableSQL('user');
        $connection->executeUpdate($truncate);

        $truncate = $platform->getTruncateTableSQL('ganttproject');
        $connection->executeUpdate($truncate);

        $connection->executeQuery('SET FOREIGN_KEY_CHECKS = 1');
    }

    public function testFindLatestGanttProject()
    {
        $gp = $this
            ->dem
            ->getRepository(GanttProject::class)
            ->findLatestGanttProject($this->user)
        ;

        $this->assertSame('TestFilename.gan', $gp->getFileName('TestFilename.gan'));
        $this->assertSame(3, $gp->getVersion());
    }

    public function testNotFoundLatestGanttProject()
    {
        $user = new User();
        $user->setId(1);

        $gp = $this
            ->dem
            ->getRepository(GanttProject::class)
            ->findLatestGanttProject($user)
        ;

        $this->assertSame(null, $gp);
    }
}
