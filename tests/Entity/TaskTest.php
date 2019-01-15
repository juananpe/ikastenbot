<?php

declare(strict_types=1);

namespace IkastenBot\Tests\Entity;

use IkastenBot\Entity\Task;
use PHPUnit\Framework\TestCase;

final class TaskTest extends TestCase
{
    /**
     * Test task
     *
     * @var Task
     */
    private $task;

    public function setUp()
    {
        $this->task = new Task();
        $this->task->setDate(new \DateTime('2021-01-01'));
    }

    public function testDelayDateFiveDays()
    {
        $this->task->delayDate(5);

        $this->assertSame('2021-01-06', $this->task->getDate()->format('Y-m-d'));
    }

    public function testDelayDateOneMonthTenDays()
    {
        $this->task->delayDate(41);

        $this->assertSame('2021-02-11', $this->task->getDate()->format('Y-m-d'));
    }

    public function testDelayDatePlusOneYear()
    {
        $this->task->delayDate(365);

        $this->assertSame('2022-01-01', $this->task->getDate()->format('Y-m-d'));
    }
}
