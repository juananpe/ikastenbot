<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Task;
use App\Service\GanttMistakeNotifier;
use App\Service\MessageSenderService;
use Longman\TelegramBot\Entities\ServerResponse;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Service\GanttMistakeNotifier
 *
 * @internal
 */
final class GanttMistakeNotifierTest extends TestCase
{
    /**
     * @var GanttMistakeNotifier
     */
    private $gmn;

    public function setUp()
    {
        $mss = new ProxyMessageSenderService();
        $this->gmn = new GanttMistakeNotifier(0, $mss);
    }

    /**
     * @covers \App\Service\GanttMistakeNotifier::notifyLackOfMilestones
     */
    public function testMilestones()
    {
        $chatId = 0;
        $tasks = [];
        $tasks[] = $this->createProxyTask($chatId, 0, 'taks1', 1);
        $tasks[] = $this->createProxyTask($chatId, 2, 'taks2', 1);
        $tasks[] = $this->createProxyTask($chatId, 3, 'taks3', 1);

        self::assertEquals(false, $this->gmn->notifyLackOfMilestones($tasks));

        $tasks[] = $this->createProxyTask($chatId, 4, 'task4', 1, true);

        self::assertEquals(true, $this->gmn->notifyLackOfMilestones($tasks));
    }

    /**
     * @covers \App\Service\GanttMistakeNotifier::notifyLackOfMeetings
     */
    public function testMeetings()
    {
        $chatId = 0;
        $tasks = [];
        $tasks[] = $this->createProxyTask($chatId, 0, 'taks1', 1);
        $tasks[] = $this->createProxyTask($chatId, 2, 'taks2', 1);
        $tasks[] = $this->createProxyTask($chatId, 3, 'taks3', 1);

        self::assertEquals(false, $this->gmn->notifyLackOfMeetings($tasks));

        $tasks[] = $this->createProxyTask($chatId, 4, 'Reuniones con el director', 1);

        self::assertEquals(true, $this->gmn->notifyLackOfMeetings($tasks));
    }

    private function createProxyTask(int $chat_id, int $id, string $name, int $duration, bool $isMilestone = false)
    {
        $task = new Task();
        $task->setChat_id(strval($chat_id));
        $task->setId($id);
        $task->setName($name);
        $task->setDuration($duration);
        $task->setIsMilestone($isMilestone);

        return $task;
    }
}

final class ProxyMessageSenderService extends MessageSenderService
{
    public function sendMessage(): ServerResponse
    {
        return new ProxyServerResponse();
    }
}

final class ProxyServerResponse extends ServerResponse
{
    public function __construct()
    {
        parent::__construct([], '');
    }

    protected function validate()
    {
    }
}
