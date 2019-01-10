<?php

declare(strict_types=1);

use IkastenBot\Entity\Task;
use IkastenBot\Tests\DatabaseTestCase;
use IkastenBot\Utils\MessageFormatterUtils;

class TaskRepositoryTest extends DatabaseTestCase
{
    /**
     * Database connection
     *
     * @var PHPUnit\DbUnit\Database\Connection
     */
    private $connection;

    /**
     * PDO object
     *
     * @var PDO
     */
    private $pdo;

    /**
     * Doctrine entity manager
     *
     * @var Doctrine\ORM\EntityManager
     */
    private $dem;

    /**
     * Array containing the days to be checked for tasks
     *
     * @var array
     */
    private $plusDays;

    public function setUp(): void
    {
        $this->connection = $this->getConnection();
        $this->pdo = $this->connection->getConnection();
        $this->pdo->beginTransaction();

        // Insert test chat to avoid triggering foreign key constraints
        $insert_test_chat = 'INSERT INTO `chat` (id) VALUES (12345)';
        $statement = $this->pdo->prepare($insert_test_chat);
        $statement->execute();

        // Insert a test user to avoid triggering foreign key constraints
        $insertTestUser = '
            INSERT INTO `user` (
                `id`,
                `is_bot`,
                `first_name`,
                `last_name`,
                `username`,
                `language_code`,
                `created_at`,
                `updated_at`,
                `language`
            ) VALUES (
                12345,
                0,
                "Test",
                "User",
                "TestUsername",
                "en",
                "2021-01-01 00:00:00",
                "2021-01-01 00:00:00",
                "es"
            )
        ';

        $statement = $this->pdo->prepare($insertTestUser);
        $statement->execute();

        // Insert a test GanttProject to avoid triggering foreign key constraints
        $insertTestGanttProject = '
            INSERT INTO `ganttproject` (
                `id`,
                `file_name`,
                `version`,
                `user_id`
            ) VALUES (
                1,
                "Test.gan",
                1,
                12345
            )'
        ;

        $statement = $this->pdo->prepare($insertTestGanttProject);
        $statement->execute();

        $sql = '
            INSERT INTO `task` (
                `gan_id`,
                `chat_id`,
                `task_name`,
                `task_date`,
                `task_isMilestone`,
                `task_duration`,
                `ganttproject_id`
            ) VALUES (
                1,
                12345,
                :task_name,
                :task_date,
                :task_isMilestone,
                :task_duration,
                1
            )
        ';

        // Insert three tasks to be reached today
        for ($i = 0; $i < 3; $i++) {
            $task = new Task();
            $today = new \DateTime();

            $today = new \DateTime();
            $parameters = [
                ':task_name'        => 'Task T',
                ':task_date'        => $today->format('Y-m-d'),
                ':task_isMilestone' => false,
                ':task_duration'    => 3
            ];

            $statement = $this->pdo->prepare($sql);
            $statement->execute($parameters);
        }

        // Insert one milestone to be reached today
        $parameters = [
            ':task_name' => 'Milestone T',
            ':task_date' => $today->format('Y-m-d'),
            ':task_isMilestone' => true,
            ':task_duration'    => 0
        ];
        $statement = $this->pdo->prepare($sql);
        $statement->execute($parameters);

        // Insert tasks to be reminded of, and three tasks that should
        // not be fetched in the queries
        $this->plusDays = [
            'P3D',
            'P3D',
            'P15D',
            'P30D',
            'P100D'
        ];

        $j = 0;
        for ($i = 1; $i <= 15; $i++) {
            $today = new \DateTime();
            $todayPlusDays = $today->add(new \DateInterval($this->plusDays[$j]));

            $parameters = [
                ':task_name' => $this->plusDays[$j],
                ':task_date' => $todayPlusDays->format('Y-m-d'),
                ':task_isMilestone' => false,
                ':task_duration'    => 3
            ];
            $statement = $this->pdo->prepare($sql);
            $statement->execute($parameters);

            if ($i % 3 === 0) {
                $parameters = [
                    ':task_name' => $this->plusDays[$j],
                    ':task_date' => $todayPlusDays->format('Y-m-d'),
                    ':task_isMilestone' => true,
                    ':task_duration'    => 0
                ];

                $statement = $this->pdo->prepare($sql);
                $statement->execute($parameters);

                $j++;
            }
        }

        $this->dem = $this->getDoctrineEntityManager();
    }

    public function tearDown(): void
    {
        $this->pdo->rollBack();
    }

    public function testFindTodayTasks()
    {
        $tasks = $this->dem->getRepository(Task::class)->findTasksReachToday();

        // Check that only three tasks have been fetched
        $this->assertSame(4, \count($tasks));

        // Check the name and the date of the tasks
        $today = new \DateTime();
        $dateFormat = 'Y-m-d';

        foreach ($tasks as $i=>$task) {
            if (3 === $i) {
                $this->assertSame('Milestone T', $task->getName());
            } else {
                $this->assertSame('Task T', $task->getName());
            }
            $this->assertSame($today->format($dateFormat), $task->getDate()->format($dateFormat));
        }
    }

    public function testFindTasksNotifyAbout()
    {
        $results = $this->dem->getRepository(Task::class)->findTasksToNotifyAbout();

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
        };

        // Check the name and the date of each of the task fetched
        foreach ($results as $row) {
            $this->assertTrue(\in_array($row[0]->getName(), $this->plusDays));
            $this->assertTrue(\in_array($row[0]->getDate()->format('Y-m-d'), $expectedDates));
        }
    }

    public function testFindTodayTasksRestrictToMilestones()
    {
        $tasks = $this->dem->getRepository(Task::class)->findTasksReachToday(true);

        // Check that only one milestone has been fetched
        $this->assertSame(1, \count($tasks));

        // Check the name, date and milestone status of the task
        $today = new \DateTime();
        $dateFormat = 'Y-m-d';

        $taskName           = $tasks[0]->getName();
        $taskDate           = $tasks[0]->getDate();
        $taskIsMilestone    = $tasks[0]->getIsMilestone();

        $this->assertSame('Milestone T', $taskName);
        $this->assertSame($today->format($dateFormat), $taskDate->format($dateFormat));
        $this->assertSame(true, $taskIsMilestone);
    }

    public function testFindTasksNotifyAboutRestrictToMilestones()
    {
        $results = $this->dem->getRepository(Task::class)->findTasksToNotifyAbout(true);

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
        };

        // Check the name, date and milestone status of each of the milestone
        // fetched
        foreach ($results as $row) {
            $this->assertTrue(\in_array($row[0]->getName(), $this->plusDays));
            $this->assertTrue(\in_array($row[0]->getDate()->format('Y-m-d'), $expectedDates));
            $this->assertSame($row[0]->getIsMilestone(), true);
        }
    }
}
