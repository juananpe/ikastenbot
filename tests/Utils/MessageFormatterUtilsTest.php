<?php

declare(strict_types=1);

use IkastenBot\Entity\Milestone;
use IkastenBot\Utils\MessageFormatterUtils;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final class MessageFormattrUtilsTest extends TestCase
{
    /**
     * Message formatter utils
     *
     * @var MessageFormatterUtils
     */
    private $mfu;
    /**
     * Twig
     *
     * @var Environment
     */
    private $twig;

    public function setUp()
    {
        $loader = new FilesystemLoader(PROJECT_ROOT . '/templates/');
        $this->twig = new Environment($loader, array(
            'cache' => PROJECT_ROOT . '/var/cache/',
        ));

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

    public function testAppendMilestone()
    {
        $milestone = new Milestone();
        $milestone->setName('Milestone');
        $milestone->setDate(new \DateTime());

        $expectedText = '';
        $expectedText .= $this->twig->render(
            'notifications/milestone.twig',
            ['milestone' => $milestone]
        );
        $expectedText .= PHP_EOL;

        $result = '';
        $this->mfu->appendMilestone($result, $milestone);

        $this->assertSame($expectedText, $result);
    }

    public function testAppendMilestoneWithDaysLeft()
    {
        $milestone = new Milestone();
        $milestone->setName('Milestone');
        $milestone->setDate(new \DateTime());

        $daysLeft = '5';

        $expectedText = '';
        $expectedText .= $this->twig->render(
            'notifications/milestone.twig',
            [
                'milestone' => $milestone,
                'daysLeft'  => $daysLeft
            ]
        );
        $expectedText .= PHP_EOL;

        $result = '';
        $this->mfu->appendMilestone($result, $milestone, $daysLeft);

        $this->assertSame($expectedText, $result);
    }

    public function testAppendMultipleThings()
    {
        $milestone = new Milestone();
        $milestone->setName('Milestone');
        $milestone->setDate(new \DateTime());

        $daysLeft = '5';

        $expectedText = 'Lorem ipsum dolor sit amet';
        $expectedText .= $this->twig->render(
            'notifications/milestone.twig',
            ['milestone' => $milestone]
        );
        $expectedText .= PHP_EOL;
        $expectedText .= $this->twig->render(
            'notifications/milestone.twig',
            [
                'milestone' => $milestone,
                'daysLeft'  => $daysLeft
            ]
        );
        $expectedText .= PHP_EOL;
        $expectedText .= 'Lorem ipsum dolor sit amet';
        $expectedText .= $this->twig->render('notifications/milestoneTodayText.twig');
        $expectedText .= PHP_EOL;
        $expectedText .= 'Lorem ipsum dolor sit amet';

        $result = 'Lorem ipsum dolor sit amet';
        $this->mfu->appendMilestone($result, $milestone);
        $this->mfu->appendMilestone($result, $milestone, $daysLeft);
        $result .= 'Lorem ipsum dolor sit amet';
        $this->mfu->appendTwigFile($result, 'notifications/milestoneTodayText.twig');
        $result .= 'Lorem ipsum dolor sit amet';

        $this->assertSame($expectedText, $result);
    }
}
