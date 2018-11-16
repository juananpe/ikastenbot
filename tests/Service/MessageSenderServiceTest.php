<?php

declare(strict_types=1);

use Longman\TelegramBot\Entities\Keyboard;
use PHPUnit\Framework\TestCase;
use TelegramBotGanttProject\Service\MessageSenderService;

class MessageSenderServiceTest extends TestCase
{

    /**
     * Message sender service
     *
     * @var MessageSenderService
     */
    protected $mss;

    public function setUp()
    {
        $this->mss = new MessageSenderService();
    }

    public function testSimpleMessage()
    {
        $this->mss->prepareMessage(12345, 'Test text');
        $result = $this->mss->getData();

        $expectedValues = [
            'chat_id' => 12345,
            'text'    => 'Test text'
        ];

        foreach ($expectedValues as $key => $ev) {
            $this->assertSame($ev, $result[$key]);
        }
    }

    public function testParseModeHtml()
    {
        $this->mss->prepareMessage(12345, '<b>Test</b> text', 'HTML');
        $result = $this->mss->getData();

        $expectedValues = [
            'chat_id'       => 12345,
            'text'          => '<b>Test</b> text',
            'parse_mode'    => 'HTML'
        ];

        foreach ($expectedValues as $key => $ev) {
            $this->assertSame($ev, $result[$key]);
        }
    }

    public function testParseModeMarkdown()
    {
        $this->mss->prepareMessage(12345, '**Test** text', 'Markdown');
        $result = $this->mss->getData();

        $expectedValues = [
            'chat_id'       => 12345,
            'text'          => '**Test** text',
            'parse_mode'    => 'Markdown'
        ];

        foreach ($expectedValues as $key => $ev) {
            $this->assertSame($ev, $result[$key]);
        }
    }

    public function testEnableSelectiveReply()
    {
        $this->mss->prepareMessage(12345, '**Test** text', 'Markdown', true);
        $result = $this->mss->getData();

        $expectedValues = [
            'chat_id'       => 12345,
            'text'          => '**Test** text',
            'parse_mode'    => 'Markdown',
            'reply_markup'  => Keyboard::forceReply(['selective' => true])
        ];

        foreach ($expectedValues as $key => $ev) {
            $this->assertEquals($ev, $result[$key]);
        }
    }

    public function testRemoveSelectiveReply()
    {
        $this->mss->prepareMessage(12345, '<b>Test</b> text', 'HTML', false);
        $result = $this->mss->getData();

        $expectedValues = [
            'chat_id'       => 12345,
            'text'          => '<b>Test</b> text',
            'parse_mode'    => 'HTML',
            'reply_markup'  => Keyboard::remove(['selective' => true])
        ];

        foreach ($expectedValues as $key => $ev) {
            $this->assertEquals($ev, $result[$key]);
        }
    }
}
