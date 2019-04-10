<?php

declare(strict_types=1);

namespace App\Tests\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \App\Command\WebhookCommand
 *
 * @internal
 */
final class WebhookCommandTest extends KernelTestCase
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
            'both' => 'Can\'t do both!',
            'empty' => 'Nothing to do.',
        ];

        $kernel = static::createKernel();
        $application = new Application($kernel);
        $this->command = $application->find('app:webhook');
        $this->commandTester = new CommandTester($this->command);
    }

    /**
     * @covers \App\Command\WebhookCommand::execute()
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

    /**
     * @covers \App\Command\WebhookCommand::execute()
     */
    public function testBoth()
    {
        $this->commandTester->execute(
            [
                'command' => $this->command->getName(),
                '--set' => true,
                '--unset' => true,
            ]
        );

        $output = $this->commandTester->getDisplay();
        $this->assertContains(
            $this->messages['both'],
            $output
        );
    }
}
