<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Task;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Entity\Task
 *
 * @internal
 */
final class TaskTest extends TestCase
{
    /**
     * Test task.
     *
     * @var Task
     */
    private $task;

    public function setUp()
    {
        $this->task = new Task();
        $this->task->setDate(new \DateTime('2021-01-01'));
    }

    /**
     * @covers \App\Entity\Task::delayDate()
     */
    public function testDelayDateFiveDays()
    {
        $this->task->delayDate(5);

        $this->assertSame('2021-01-06', $this->task->getDate()->format('Y-m-d'));
    }

    /**
     * @covers \App\Entity\Task::delayDate()
     */
    public function testDelayDateOneMonthTenDays()
    {
        $this->task->delayDate(41);

        $this->assertSame('2021-02-11', $this->task->getDate()->format('Y-m-d'));
    }

    /**
     * @covers \App\Entity\Task::delayDate()
     */
    public function testDelayDatePlusOneYear()
    {
        $this->task->delayDate(365);

        $this->assertSame('2022-01-01', $this->task->getDate()->format('Y-m-d'));
    }
}
