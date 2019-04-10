<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Task;
use App\Service\MessageFormatterUtilsService;
use Longman\TelegramBot\Entities\InlineKeyboard;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * @covers \App\Service\MessageFormatterUtilsService
 *
 * @internal
 */
final class MessageFormatterUtilsServiceTest extends TestCase
{
    /**
     * Message formatter utils.
     *
     * @var MessageFormatterUtilsService
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
        /*
         * Define the DOWNLOAD_DIR constant required by the
         * MessageFormatterUtils
         */
        if (!\defined('PROJECT_ROOT')) {
            define('PROJECT_ROOT', __DIR__.'/../../');
        }

        $loader = new FilesystemLoader('templates/');
        $this->twig = new Environment($loader, [
            'cache' => 'var/cache/Twig',
        ]);

        $this->mfu = new MessageFormatterUtilsService();
    }

    /**
     * @covers \App\Service\MessageFormatterUtilsService::__construct()
     */
    public function testConstructorTwigNull()
    {
        $mfu = new MessageFormatterUtilsService();

        $this->assertTrue(true);
    }

    /**
     * @covers \App\Service\MessageFormatterUtilsService::__construct()
     */
    public function testConstructorGiveTwig()
    {
        $twigMock = $this->createMock(Environment::class);
        $mfu = new MessageFormatterUtilsService($twigMock);

        $this->assertTrue(true);
    }

    /**
     * @covers \App\Service\MessageFormatterUtilsService::appendTwigFile()
     */
    public function testAppendTwigFile()
    {
        $expectedText = '';
        $expectedText .= $this->twig->render('notifications/milestoneTodayText.twig');
        $expectedText .= PHP_EOL;

        $result = '';
        $this->mfu->appendTwigFile($result, 'notifications/milestoneTodayText.twig');

        $this->assertSame($expectedText, $result);
    }

    /**
     * @covers \App\Service\MessageFormatterUtilsService::appendTask()
     */
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

    /**
     * @covers \App\Service\MessageFormatterUtilsService::appendTask()
     */
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

    /**
     * @covers \App\Service\MessageFormatterUtilsService::appendTask()
     * @covers \App\Service\MessageFormatterUtilsService::appendTwigFile()
     */
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

    /**
     * @covers \App\Service\MessageFormatterUtilsService::appendTask()
     */
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

    /**
     * @covers \App\Service\MessageFormatterUtilsService::appendTask()
     */
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

    /**
     * @covers \App\Service\MessageFormatterUtilsService::appendTask()
     * @covers \App\Service\MessageFormatterUtilsService::appendTwigFile()
     */
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

    /**
     * @covers \App\Service\MessageFormatterUtilsService::appendTwigFileWithParameters()
     */
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

    /**
     * @covers \App\Service\MessageFormatterUtilsService::createThreeOptionInlineKeyboard()
     */
    public function testCreateThreeOptionKeyboardTask()
    {
        $keyboard = $this->mfu->createThreeOptionInlineKeyboard(1, false);

        $expectedInlineKeyboard = new InlineKeyboard(
            [
                [
                    'text' => '💪 Yes',
                    'callback_data' => 'affirmative_noop',
                ],
                [
                    'text' => '🥵 No, let\'s delay it',
                    'callback_data' => '/delaytask 1',
                ],
            ],
            [
                [
                    'text' => '💤 Disable this task\'s notifications',
                    'callback_data' => '/disablenotifications 1',
                ],
            ]
        );

        $this->assertEquals($expectedInlineKeyboard, $keyboard);
    }

    /**
     * @covers \App\Service\MessageFormatterUtilsService::createThreeOptionInlineKeyboard()
     */
    public function testCreateThreeOptionKeyboardMilestone()
    {
        $keyboard = $this->mfu->createThreeOptionInlineKeyboard(5, true);

        $expectedInlineKeyboard = new InlineKeyboard(
            [
                [
                    'text' => '💪 Yes',
                    'callback_data' => 'affirmative_noop',
                ],
                [
                    'text' => '🥵 No, let\'s delay it',
                    'callback_data' => '/delaytask 5',
                ],
            ],
            [
                [
                    'text' => '💤 Disable this milestone\'s notifications',
                    'callback_data' => '/disablenotifications 5',
                ],
            ]
        );

        $this->assertEquals($expectedInlineKeyboard, $keyboard);
    }
}
