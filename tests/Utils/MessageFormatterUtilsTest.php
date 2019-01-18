<?php

declare(strict_types=1);

use IkastenBot\Entity\Task;
use IkastenBot\Utils\MessageFormatterUtils;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * @internal
 * @coversNothing
 */
final class MessageFormattrUtilsTest extends TestCase
{
    /**
     * Message formatter utils.
     *
     * @var MessageFormatterUtils
     */
    private $mfu;
    /**
     * Twig.
     *
     * @var Environment
     */
    private $twig;

    public function setUp()
    {
        $loader = new FilesystemLoader(PROJECT_ROOT.'/templates/');
        $this->twig = new Environment($loader, [
            'cache' => PROJECT_ROOT.'/var/cache/',
        ]);

        $this->mfu = new MessageFormatterUtils();
    }

    public function testConstructorTwigNull()
    {
        $mfu = new MessageFormatterUtils();

        $this->assertTrue(true);
    }

    public function testConstructorGiveTwig()
    {
        $twigMock = $this->createMock(Environment::class);
        $mfu = new MessageFormatterUtils($twigMock);

        $this->assertTrue(true);
    }

    public function testAppendTwigFile()
    {
        $expectedText = '';
        $expectedText .= $this->twig->render('notifications/milestoneTodayText.twig');
        $expectedText .= PHP_EOL;

        $result = '';
        $this->mfu->appendTwigFile($result, 'notifications/milestoneTodayText.twig');

        $this->assertSame($expectedText, $result);
    }

    public function testAppendTaskMilestone()
    {
        $milestone = new Task();
        $milestone->setName('Milestone');
        $milestone->setDate(new \DateTime());
        $milestone->setIsMilestone(true);

        $expectedText = '';
        $expectedText .= $this->twig->render(
            'notifications/milestone/milestone.twig',
            ['task' => $milestone]
        );
        $expectedText .= PHP_EOL;

        $result = '';
        $this->mfu->appendTask($result, $milestone, null, $milestone->getIsMilestone());

        $this->assertSame($expectedText, $result);
    }

    public function testAppendTaskWithDaysLeftMilestone()
    {
        $milestone = new Task();
        $milestone->setName('Milestone');
        $milestone->setDate(new \DateTime());
        $milestone->setIsMilestone(true);

        $daysLeft = '5';

        $expectedText = '';
        $expectedText .= $this->twig->render(
            'notifications/milestone/milestone.twig',
            [
                'task' => $milestone,
                'daysLeft' => $daysLeft,
            ]
        );
        $expectedText .= PHP_EOL;

        $result = '';
        $this->mfu->appendTask($result, $milestone, $daysLeft, $milestone->getIsMilestone());

        $this->assertSame($expectedText, $result);
    }

    public function testAppendTaskMultipleThingsMilestone()
    {
        $milestone = new Task();
        $milestone->setName('Milestone');
        $milestone->setDate(new \DateTime());
        $milestone->setIsMilestone(true);

        $daysLeft = '5';

        $expectedText = 'Lorem ipsum dolor sit amet';
        $expectedText .= $this->twig->render(
            'notifications/milestone/milestone.twig',
            ['task' => $milestone]
        );
        $expectedText .= PHP_EOL;
        $expectedText .= $this->twig->render(
            'notifications/milestone/milestone.twig',
            [
                'task' => $milestone,
                'daysLeft' => $daysLeft,
            ]
        );
        $expectedText .= PHP_EOL;
        $expectedText .= 'Lorem ipsum dolor sit amet';
        $expectedText .= $this->twig->render('notifications/milestone/milestoneTodayText.twig');
        $expectedText .= PHP_EOL;
        $expectedText .= 'Lorem ipsum dolor sit amet';

        $result = 'Lorem ipsum dolor sit amet';
        $this->mfu->appendTask($result, $milestone, null, $milestone->getIsMilestone());
        $this->mfu->appendTask($result, $milestone, $daysLeft, $milestone->getIsMilestone());
        $result .= 'Lorem ipsum dolor sit amet';
        $this->mfu->appendTwigFile($result, 'notifications/milestone/milestoneTodayText.twig');
        $result .= 'Lorem ipsum dolor sit amet';

        $this->assertSame($expectedText, $result);
    }

    public function testAppendTask()
    {
        $task = new Task();
        $task->setName('Task');
        $task->setDate(new \DateTime());

        $expectedText = '';
        $expectedText .= $this->twig->render(
            'notifications/task/task.twig',
            ['task' => $task]
        );
        $expectedText .= PHP_EOL;

        $result = '';
        $this->mfu->appendTask($result, $task);

        $this->assertSame($expectedText, $result);
    }

    public function testAppendTaskWithDaysLeft()
    {
        $task = new Task();
        $task->setName('Task');
        $task->setDate(new \DateTime());

        $daysLeft = '5';

        $expectedText = '';
        $expectedText .= $this->twig->render(
            'notifications/task/task.twig',
            [
                'task' => $task,
                'daysLeft' => $daysLeft,
            ]
        );
        $expectedText .= PHP_EOL;

        $result = '';
        $this->mfu->appendTask($result, $task, $daysLeft);

        $this->assertSame($expectedText, $result);
    }

    public function testAppendMultipleThingsWithTasks()
    {
        $task = new Task();
        $task->setName('Task');
        $task->setDate(new \DateTime());

        $daysLeft = '5';

        $expectedText = 'Lorem ipsum dolor sit amet';
        $expectedText .= $this->twig->render(
            'notifications/task/task.twig',
            ['task' => $task]
        );
        $expectedText .= PHP_EOL;
        $expectedText .= $this->twig->render(
            'notifications/task/task.twig',
            [
                'task' => $task,
                'daysLeft' => $daysLeft,
            ]
        );
        $expectedText .= PHP_EOL;
        $expectedText .= 'Lorem ipsum dolor sit amet';
        $expectedText .= $this->twig->render('notifications/task/taskTodayText.twig');
        $expectedText .= PHP_EOL;
        $expectedText .= 'Lorem ipsum dolor sit amet';

        $result = 'Lorem ipsum dolor sit amet';
        $this->mfu->appendTask($result, $task);
        $this->mfu->appendTask($result, $task, $daysLeft);
        $result .= 'Lorem ipsum dolor sit amet';
        $this->mfu->appendTwigFile($result, 'notifications/task/taskTodayText.twig');
        $result .= 'Lorem ipsum dolor sit amet';

        $this->assertSame($expectedText, $result);
    }

    public function testAppendWithParameters()
    {
        $task = new Task();
        $task->setName('Task');
        $task->setDate(new \DateTime());

        $expectedText = 'Lorem ipsum dolor sit amet';
        $expectedText .= $this->twig->render(
            'notifications/task/task.twig',
            ['task' => $task]
        );
        $expectedText .= PHP_EOL;
        $expectedText .= $this->twig->render(
            'notifications/task/task.twig',
            ['task' => $task]
        );
        $expectedText .= PHP_EOL;

        $parameters = [
            'task' => $task,
        ];

        $result = 'Lorem ipsum dolor sit amet';
        $this->mfu->appendTwigFileWithParameters(
            $result,
            'notifications/task/task.twig',
            $parameters
        );

        $this->mfu->appendTwigFileWithParameters(
            $result,
            'notifications/task/task.twig',
            $parameters
        );

        $this->assertSame($expectedText, $result);
    }
}
