<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\GanttProject;
use App\Entity\User;
use App\Tests\DatabaseTestCase;

/**
 * @covers \App\Repository\GanttProjectRepository
 *
 * @internal
 */
class GanttProjectRepositoryTest extends DatabaseTestCase
{
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
        $this->dem = $this->getEntityManager();

        $fixedDate = new \DateTime('2021-01-01');

        $this->user = new User();
        $this->user->setId('12345');
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
        $tables = [
            'ganttproject',
            'user',
        ];

        $this->truncateTables($tables);
        $this->closeEntityManager();
    }

    /**
     * @covers \App\Repository\GanttProjectRepository::findLatestGanttProject()
     */
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

    /**
     * @covers \App\Repository\GanttProjectRepository::findLatestGanttProject()
     */
    public function testNotFoundLatestGanttProject()
    {
        $user = new User();
        $user->setId('1');

        $gp = $this
            ->dem
            ->getRepository(GanttProject::class)
            ->findLatestGanttProject($user)
        ;

        $this->assertSame(null, $gp);
    }
}
