<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\GanttProject;
use App\Entity\Task;
use App\Service\NotificationManagerService;
use App\Tests\Fixtures\GanttProjectDataLoader;
use App\Tests\Fixtures\TaskDataLoader;
use App\Tests\Fixtures\UserDataLoader;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @covers \App\Service\NotificationManagerService
 *
 * @internal
 */
class NotificationManagerServiceDbTest extends KernelTestCase
{
    public function setUp(): void
    {
        // Get the entity manager
        $kernel = self::bootKernel();

        $this->em = $kernel->getContainer()
            ->get('doctrine')
            ->getManager()
        ;

        // Get pdo
        $pdo = $this->em->getConnection()->getWrappedConnection();

        // Insert a test chat to avoid the foreign key constraints
        $insert_test_chat = 'INSERT INTO chat (id) VALUES (12345)';
        $statement = $pdo->prepare($insert_test_chat);
        $statement->execute();

        for ($i = 0; $i < 5; ++$i) {
            // Load fixtures into the database for the tests
            $loader = new Loader();

            if ($i > 0) {
                $connection = $this->em->getConnection();
                $platform = $connection->getDatabasePlatform();

                $connection->executeQuery('SET FOREIGN_KEY_CHECKS = 0');

                $truncate = $platform->getTruncateTableSQL('user');
                $connection->executeUpdate($truncate);

                $connection->executeQuery('SET FOREIGN_KEY_CHECKS = 1');
            }
            $loader->addFixture(new UserDataLoader());
            $loader->addFixture(new GanttProjectDataLoader(null, $i + 1, null));
            $loader->addFixture(new TaskDataLoader());

            $executor = new ORMExecutor($this->em, new ORMPurger());
            $executor->execute($loader->getFixtures(), true);
        }
    }

    public function tearDown(): void
    {
        $connection = $this->em->getConnection();
        $platform = $connection->getDatabasePlatform();

        $connection->executeQuery('SET FOREIGN_KEY_CHECKS = 0');

        $truncate = $platform->getTruncateTableSQL('chat');
        $connection->executeUpdate($truncate);

        $truncate = $platform->getTruncateTableSQL('user');
        $connection->executeUpdate($truncate);

        $truncate = $platform->getTruncateTableSQL('ganttproject');
        $connection->executeUpdate($truncate);

        $truncate = $platform->getTruncateTableSQL('task');
        $connection->executeUpdate($truncate);

        $connection->executeQuery('SET FOREIGN_KEY_CHECKS = 1');

        $this->em->close();
        $this->em = null; // avoid memory leaks
    }

    /**
     * @covers \App\Service\NotificationManagerService::disableNotificationsForOutdatedTasks()
     */
    public function testDisableNotificationsOldTasks()
    {
        $latestGanttProject = $this->em->getRepository(GanttProject::class)->find(5);
        $notificationManagerService = new NotificationManagerService($this->em);

        // Disable notifications for old tasks
        $notificationManagerService->disableNotificationsForOutdatedTasks(12345, $latestGanttProject);

        $taskRepository = $this->em->getRepository(Task::class);
        $resultingCollection = $taskRepository->findFromPreviousVersionsOfGanttProject(12345, $latestGanttProject);

        // There shouldn't be any tasks related to a GanttProject with a version
        // lower than 5
        $this->assertSame(0, \count($resultingCollection));

        // Check that the only tasks enabled for notifications are from the
        // latest version of the GanttProject
        $resultingCollection = $taskRepository->findBy(['notify' => 1]);

        $this->assertSame(24, \count($resultingCollection));
        foreach ($resultingCollection as $task) {
            $this->assertSame(5, $task->getGanttProject()->getVersion());
            $this->assertSame(true, $task->getNotify());
        }
    }
}
