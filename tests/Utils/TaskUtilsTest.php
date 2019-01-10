<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use IkastenBot\Entity\Task;
use IkastenBot\Exception\IncorrectFileException;
use IkastenBot\Exception\NoMilestonesException;
use IkastenBot\Utils\TaskUtils;
use PHPUnit\Framework\TestCase;

final class TaskUtilsTest extends TestCase
{
    /**
     * Task utils
     *
     * @var TaskUtils
     */
    private $tu;

    /**
     * Test task
     *
     * @var Task
     */
    private $task;

    public function setUp()
    {
        $em = $this->createMock(EntityManager::class);

        $this->tu = new TaskUtils($em);

        $this->task = new Task();
        $this->task->setDate(new \DateTime('2021-01-01'));
    }

    public function testUpdateTaskDateWithOffsetPositive()
    {
        $result = $this->tu->updateTaskDateWithOffset(
            $this->task,
            5
        );

        $this->assertSame('2021-01-06', $result->getDate()->format('Y-m-d'));
    }

    public function testUpdateTaskDateWithOffsetNegative()
    {
        $result = $this->tu->updateTaskDateWithOffset(
            $this->task,
            -5
        );

        $this->assertSame('2020-12-27', $result->getDate()->format('Y-m-d'));
    }

    public function testUpdateTaskDateWithOffsetPlusOneMonthTenDays()
    {
        $result = $this->tu->updateTaskDateWithOffset(
            $this->task,
            41
        );

        $this->assertSame('2021-02-11', $result->getDate()->format('Y-m-d'));
    }

    public function testUpdateTaskDateWithOffsetMinusOneMonthTenDays()
    {
        $result = $this->tu->updateTaskDateWithOffset(
            $this->task,
            -41
        );

        $this->assertSame('2020-11-21', $result->getDate()->format('Y-m-d'));
    }

    public function testUpdateTaskDateWithOffsetPlusOneYear()
    {
        $result = $this->tu->updateTaskDateWithOffset(
            $this->task,
            365
        );

        $this->assertSame('2022-01-01', $result->getDate()->format('Y-m-d'));
    }

    public function testUpdateTaskDateWithOffsetMinusOneYear()
    {
        $result = $this->tu->updateTaskDateWithOffset(
            $this->task,
            -366
        );

        $this->assertSame('2020-01-01', $result->getDate()->format('Y-m-d'));
    }
}
