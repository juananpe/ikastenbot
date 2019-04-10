<?php

declare(strict_types=1);

namespace App\Tests\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \App\Command\BroadcastCommand
 *
 * @internal
 */
final class BroadcastCommandTest extends KernelTestCase
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
            'empty' => 'Nothing to do.',
        ];

        $kernel = static::createKernel();
        $application = new Application($kernel);
        $this->command = $application->find('app:broadcast');
        $this->commandTester = new CommandTester($this->command);
    }

    /**
     * @covers \App\Command\BroadcastCommand::execute()
     */
    public function testNoParams()
    {
        $this->commandTester->execute(['command' => $this->command->getName()]);

        $output = $this->commandTester->getDisplay();
        $this->assertContains(
            $this->messages['empty'],
            $output
        );
    }
}
