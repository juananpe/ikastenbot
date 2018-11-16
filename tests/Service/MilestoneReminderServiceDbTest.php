<?php

declare(strict_types=1);

use TelegramBotGanttProject\Service\MilestoneReminderService;
use TelegramBotGanttProject\Service\MessageSenderService;
use TelegramBotGanttProject\Tests\DatabaseTestCase;
use TelegramBotGanttProject\Utils\MessageFormatterUtils;

class MilestoneReminderServiceTest extends DatabaseTestCase
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
     * Array containing the days to be checked for milestones
     *
     * @var array
     */
    private $plusDays;

    public function setUp(): void
    {
        $this->connection = $this->getConnection();
        $this->pdo = $this->connection->getConnection();
        $this->pdo->beginTransaction();

        $insert_test_chat = 'INSERT INTO chat (id) VALUES (12345)';
        $statement = $this->pdo->prepare($insert_test_chat);
        $statement->execute();

        // Insert test chat to avoid foreign key triggering
        $sql = 'INSERT INTO milestone (chat_id, milestone_name, milestone_date)
                VALUES (12345, :milestone_name, :milestone_date)
        ';

        // Insert three milestones to be reached today
        for ($i = 0; $i < 3; $i++) {
            $today = new \DateTime();
            $parameters = [
                ':milestone_name' => 'Milestone T',
                ':milestone_date' => $today->format('Y-m-d')
            ];

            $statement = $this->pdo->prepare($sql);
            $statement->execute($parameters);
        }

        // Insert milestones to be reminded of, and three milestones that should
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
                ':milestone_name' => $this->plusDays[$j],
                ':milestone_date' => $todayPlusDays->format('Y-m-d')
            ];
            $statement = $this->pdo->prepare($sql);
            $statement->execute($parameters);

            if ($i % 3 === 0) {
                $j++;
            }
        }

        $this->dem = $this->getDoctrineEntityManager();
    }

    public function tearDown(): void
    {
        $this->pdo->rollBack();
    }

    public function testFindTodayMilestones()
    {
        $mssMock = $this->createMock(MessageSenderService::class);
        $mfuMock = $this->createMock(MessageFormatterUtils::class);

        $mrs = new MilestoneReminderService($this->dem, $mfuMock, $mssMock);
        $milestones = $mrs->findMilestonesReachToday();

        // Check that only three milestones have been fetched
        $this->assertSame(3, \count($milestones));

        // Check the name and the date of the milestones
        $today = new \DateTime();
        $dateFormat = 'Y-m-d';
        foreach ($milestones as $milestone) {
            $this->assertSame('Milestone T', $milestone->getName());
            $this->assertSame($today->format($dateFormat), $milestone->getDate()->format($dateFormat));
        }
    }

    public function testFindMilestonesClose()
    {
        $mssMock = $this->createMock(MessageSenderService::class);
        $mfuMock = $this->createMock(MessageFormatterUtils::class);

        $mrs = new MilestoneReminderService($this->dem, $mfuMock, $mssMock);
        $results = $mrs->findMilestonesToNotifyAbout();

        // Check that only the correct amount of milestones have been fetched
        $this->assertSame(12, \count($results));

        // Get the time intervals which correspond to the milestones that
        // should have been fetched
        $plusDaysWithoutLast = \array_slice($this->plusDays, 0, \count($this->plusDays) - 1);

        // Prepare future dates to check against
        $expectedDates = [];
        foreach ($plusDaysWithoutLast as $futureDate) {
            $today = new \DateTime();
            $today->add(new \DateInterval($futureDate));
            $expectedDates[] = $today->format('Y-m-d');
        };

        // Check the name and the date of each of the milestone fetched
        foreach ($results as $row) {
            $this->assertTrue(\in_array($row[0]->getName(), $this->plusDays));
            $this->assertTrue(\in_array($row[0]->getDate()->format('Y-m-d'), $expectedDates));
        }
    }
}
