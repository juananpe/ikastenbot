<?php

declare(strict_types=1);

namespace App\Tests\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \App\Command\MTRemindersCommand
 *
 * @internal
 */
final class MTRemindersCommandTest extends KernelTestCase
{
    /**
     * The command to be executed.
     *
     * @var \Symfony\Component\Console\Command\Command
     */
    private $command;

    /**
     * Command tester.
     *
     * @var CommandTester
     */
    private $commandTester;

    /**
     * Array containing the output messages of the command.
     *
     * @var array
     */
    private $messages;

    public function setUp()
    {
        $this->messages = [
            'today' => [
                'milestone' => "Notifications for today's milestones sent!\n",
                'task' => "Notifications for today's tasks sent!\n",
            ],
            'milestone' => "Notifications about the relevant milestones's deadlines sent!\n",
            'task' => "Notifications about the relevant tasks's deadlines sent!\n",
        ];

        $kernel = static::createKernel();
        $application = new Application($kernel);
        $this->command = $application->find('app:mt-send-reminders');
        $this->commandTester = new CommandTester($this->command);
    }

    /**
     * @covers \App\Command\MTRemindersCommand::execute()
     */
    public function testNoParams()
    {
        $this->commandTester->execute(['command' => $this->command->getName()]);

        $output = $this->commandTester->getDisplay();
        $this->assertContains(
            $this->messages['milestone'].$this->messages['task'],
            $output
        );
    }

    /**
     * @covers \App\Command\MTRemindersCommand::execute()
     */
    public function testTasks()
    {
        $this->commandTester->execute(
            [
                'command' => $this->command->getName(),
                '--tasks' => true,
            ]
        );

        $output = $this->commandTester->getDisplay();
        $this->assertContains(
            $this->messages['task'],
            $output
        );
    }

    /**
     * @covers \App\Command\MTRemindersCommand::execute()
     */
    public function testMilestones()
    {
        $this->commandTester->execute(
            [
                'command' => $this->command->getName(),
                '--milestones' => true,
            ]
        );

        $output = $this->commandTester->getDisplay();
        $this->assertContains(
            $this->messages['milestone'],
            $output
        );
    }

    /**
     * @covers \App\Command\MTRemindersCommand::execute()
     */
    public function testToday()
    {
        $this->commandTester->execute(
            [
                'command' => $this->command->getName(),
                '--today' => true,
            ]
        );

        $output = $this->commandTester->getDisplay();
        $this->assertContains(
            $this->messages['today']['milestone'].$this->messages['today']['task'],
            $output
        );
    }

    /**
     * @covers \App\Command\MTRemindersCommand::execute()
     */
    public function testTodayTasks()
    {
        $this->commandTester->execute(
            [
                'command' => $this->command->getName(),
                '--today' => true,
                '--tasks' => true,
            ]
        );

        $output = $this->commandTester->getDisplay();
        $this->assertContains(
            $this->messages['today']['task'],
            $output
        );
    }

    /**
     * @covers \App\Command\MTRemindersCommand::execute()
     */
    public function testTodayMilestones()
    {
        $this->commandTester->execute(
            [
                'command' => $this->command->getName(),
                '--today' => true,
                '--milestones' => true,
            ]
        );

        $output = $this->commandTester->getDisplay();
        $this->assertContains(
            $this->messages['today']['milestone'],
            $output
        );
    }
}
