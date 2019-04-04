<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\GanttProject;
use App\Entity\Task;
use App\Tests\Fixtures\GanttProjectDataLoader;
use App\Tests\Fixtures\TaskDataLoader;
use App\Tests\Fixtures\UserDataLoader;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @covers \App\Repository\GanttProjectRepository
 *
 * @internal
 */
class TaskRepositoryTest extends KernelTestCase
{
    /**
     * Doctrine entity manager.
     *
     * @var Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * Array containing the days to be checked for tasks.
     *
     * @var array
     */
    private $plusDays;

    public function setUp(): void
    {
        // Get entity manager
        $kernel = self::bootKernel();

        $this->em = $kernel->getContainer()
            ->get('doctrine')
            ->getManager()
        ;

        // Get pdo
        $pdo = $this->em->getConnection()->getWrappedConnection();

        // Insert test chat to avoid triggering foreign key constraints
        $insert_test_chat = 'INSERT INTO `chat` (id) VALUES (12345)';
        $statement = $pdo->prepare($insert_test_chat);
        $statement->execute();

        // Load fixtures into the database for the tests
        $loader = new Loader();
        $loader->addFixture(new UserDataLoader());
        $loader->addFixture(new GanttProjectDataLoader());
        $loader->addFixture(new TaskDataLoader());

        $purger = new ORMPurger();
        $executor = new ORMExecutor($this->em, $purger);
        $executor->execute($loader->getFixtures());

        // Intervals used in TaskDataLoader to create the tasks
        $this->plusDays = [
            'P3D',
            'P3D',
            'P15D',
            'P30D',
            'P100D',
        ];
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
     * @covers \App\Repository\TaskRepository::findTasksReachToday()
     */
    public function testFindTodayTasks()
    {
        $tasks = $this->em->getRepository(Task::class)->findTasksReachToday();

        // Check that only three tasks have been fetched
        $this->assertSame(4, \count($tasks));

        // Check the name and the date of the tasks
        $today = new \DateTime();
        $dateFormat = 'Y-m-d';

        foreach ($tasks as $i => $task) {
            if (3 === $i) {
                $this->assertSame('Milestone T', $task->getName());
            } else {
                $this->assertSame('Task T', $task->getName());
            }
            $this->assertSame($today->format($dateFormat), $task->getDate()->format($dateFormat));
        }
    }

    /**
     * @covers \App\Repository\TaskRepository::findTasksToNotifyAbout()
     */
    public function testFindTasksNotifyAbout()
    {
        $results = $this->em->getRepository(Task::class)->findTasksToNotifyAbout();

        // Check that only the correct amount of tasks have been fetched
        $this->assertSame(16, \count($results));

        // Get the time intervals which correspond to the tasks that
        // should have been fetched
        $plusDaysWithoutLast = \array_slice($this->plusDays, 0, \count($this->plusDays) - 1);

        // Prepare future dates to check against
        $expectedDates = [];
        foreach ($plusDaysWithoutLast as $futureDate) {
            $today = new \DateTime();
            $today->add(new \DateInterval($futureDate));
            $expectedDates[] = $today->format('Y-m-d');
        }

        // Check the name and the date of each of the task fetched
        foreach ($results as $row) {
            $this->assertTrue(\in_array($row[0]->getName(), $this->plusDays));
            $this->assertTrue(\in_array($row[0]->getDate()->format('Y-m-d'), $expectedDates));
        }
    }

    /**
     * @covers \App\Repository\TaskRepository::findTasksReachToday()
     */
    public function testFindTodayTasksRestrictToMilestones()
    {
        $tasks = $this->em->getRepository(Task::class)->findTasksReachToday(true);

        // Check that only one milestone has been fetched
        $this->assertSame(1, \count($tasks));

        // Check the name, date and milestone status of the task
        $today = new \DateTime();
        $dateFormat = 'Y-m-d';

        $taskName = $tasks[0]->getName();
        $taskDate = $tasks[0]->getDate();
        $taskIsMilestone = $tasks[0]->getIsMilestone();

        $this->assertSame('Milestone T', $taskName);
        $this->assertSame($today->format($dateFormat), $taskDate->format($dateFormat));
        $this->assertSame(true, $taskIsMilestone);
    }

    /**
     * @covers \App\Repository\TaskRepository::findTasksToNotifyAbout()
     */
    public function testFindTasksNotifyAboutRestrictToMilestones()
    {
        $results = $this->em->getRepository(Task::class)->findTasksToNotifyAbout(true);

        // Check that only four milestones have been fetched
        $this->assertSame(4, \count($results));

        // Get the time intervals which correspond to the tasks that
        // should have been fetched
        $plusDaysWithoutLast = \array_slice($this->plusDays, 0, \count($this->plusDays) - 1);

        // Prepare future dates to check against
        $expectedDates = [];
        foreach ($plusDaysWithoutLast as $futureDate) {
            $today = new \DateTime();
            $today->add(new \DateInterval($futureDate));
            $expectedDates[] = $today->format('Y-m-d');
        }

        // Check the name, date and milestone status of each of the milestone
        // fetched
        foreach ($results as $row) {
            $this->assertTrue(\in_array($row[0]->getName(), $this->plusDays));
            $this->assertTrue(\in_array($row[0]->getDate()->format('Y-m-d'), $expectedDates));
            $this->assertSame($row[0]->getIsMilestone(), true);
        }
    }

    /**
     * @covers \App\Repository\TaskRepository::findFromPreviousVersionsOfGanttProject()
     */
    public function testFetchNoTasksFromOutdatedGanttProjects()
    {
        $ganttProject = $this->em->getRepository(GanttProject::class)->find(1);

        $result = $this->em->getRepository(Task::class)->findFromPreviousVersionsOfGanttProject(12345, $ganttProject);

        /*
         * It shouldn't find any tasks since there's only one GanttProject in the
         * database
         */
        $this->assertEquals(0, \count($result));
    }

    /**
     * @covers \App\Repository\TaskRepository::findFromPreviousVersionsOfGanttProject()
     */
    public function testFetchTasksFromOutdatedGanttProject()
    {
        $this->reloadGanttAndTaskFixtures(1);

        // Fetch the latest inserted GanttProject
        $ganttProjectRepository = $this->em->getRepository(GanttProject::class);
        $latestGanttProject = $ganttProjectRepository->find(2);

        // Fetch the tasks
        $taskRepository = $this->em->getRepository(Task::class);
        $resultCollection = $taskRepository->findFromPreviousVersionsOfGanttProject(12345, $latestGanttProject);

        // There should be 24 inserted tasks which meet the condition
        $this->assertEquals(24, \count($resultCollection));

        // Check that the GanttVersion and the notify flag are set as expected
        foreach ($resultCollection as $result) {
            $this->assertEquals(1, $result->getGanttProject()->getVersion());
            $this->assertEquals(true, $result->getNotify());
        }
    }

    /**
     * @covers \App\Repository\TaskRepository::findFromPreviousVersionsOfGanttProject()
     */
    public function testFetchTasksFromManyOutdatedGanttProjects()
    {
        $this->reloadGanttAndTaskFixtures(4);

        // Fetch the latest inserted GanttProject
        $ganttProjectRepository = $this->em->getRepository(GanttProject::class);
        $latestGanttProject = $ganttProjectRepository->find(5);

        // Fetch the tasks
        $taskRepository = $this->em->getRepository(Task::class);
        $resultCollection = $taskRepository->findFromPreviousVersionsOfGanttProject(12345, $latestGanttProject);

        // There should be 24 * 4 = 96 inserted tasks which meet the condition
        $this->assertEquals(96, \count($resultCollection));

        // Check that the GanttVersion and the notify flag are set as expected
        foreach ($resultCollection as $result) {
            $this->assertLessThan(5, $result->getGanttProject()->getVersion());
            $this->assertEquals(true, $result->getNotify());
        }
    }

    /**
     * Reloads GanttProject and Task fixtures as many times as specified. It
     * assumes that a first version of the GanttProject exists.
     *
     * @param int $times The amount of times to load the fixtures
     */
    private function reloadGanttAndTaskFixtures(int $times)
    {
        for ($i = 0; $i < $times; ++$i) {
            /*
            *The user table needs to be truncated in order to avoid duplication
            * constraint issues.
            */
            $connection = $this->em->getConnection();
            $platform = $connection->getDatabasePlatform();

            $connection->executeQuery('SET FOREIGN_KEY_CHECKS = 0');

            $truncate = $platform->getTruncateTableSQL('user');
            $connection->executeUpdate($truncate);

            $connection->executeQuery('SET FOREIGN_KEY_CHECKS = 1');

            // Load a new GanttProject and new Tasks into the database
            $loader = new Loader();
            $loader->addFixture(new UserDataLoader());
            $loader->addFixture(new GanttProjectDataLoader(null, 2 + $i, null));
            $loader->addFixture(new TaskDataLoader());
            $executor = new ORMExecutor($this->em, new ORMPurger());
            $executor->execute($loader->getFixtures(), true);
        }
    }
}
