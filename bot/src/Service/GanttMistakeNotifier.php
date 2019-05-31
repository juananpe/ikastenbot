<?php

declare(strict_types=1);

namespace App\Service;

class GanttMistakeNotifier
{
    /**
     * @var int
     */
    private $chatId;

    /**
     * @var MessageSenderService
     */
    private $mss;

    public function __construct(int $chatId, MessageSenderService $mss)
    {
        $this->mss = $mss;

        $this->chatId = $chatId;
    }

    /**
     * Check if the given tasks contain at least one milestone. If that
     * isn't the case, send a message notifying the user.
     *
     * @param array $tasks Task list
     *
     * @return bool True if there are milestones among the tasks, False otherwise
     */
    public function notifyLackOfMilestones(array $tasks): bool
    {
        if (!$this->thereAreMilestones($tasks)) {
            // If no milestones were found, send a warning message.
            $text = 'ATENCIÓN:';
            $text .= PHP_EOL.'No he detectado ningún hito (milestone) en tu diagrama Gantt.';
            $text .= PHP_EOL.'Es muy recomendable que tu diagrama tenga hitos para ayudar con el seguimiento.';
            $this->mss->prepareMessage($this->chatId, $text);
            $this->mss->sendMessage();

            return false;
        }

        return true;
    }

    /**
     * Check if the given tasks contain at least one task about meetings
     * If that isn't the case, send a message notifying the user.
     *
     * @param array $tasks Task list
     *
     * @return bool True if there are meeting tasks, False otherwise
     */
    public function notifyLackOfMeetings(array $tasks): bool
    {
        if (!$this->thereAreTrackingMeetings($tasks)) {
            // If no meetings were found, send a warning message.
            $text = 'ATENCIÓN:';
            $text .= PHP_EOL.'No he detectado ninguna tarea o hito haciendo referencia a reuniones de seguimiento.';
            $text .= PHP_EOL.'Deberías añadir reuniones regulares con el cliente/tutor del proyecto.';
            $this->mss->prepareMessage($this->chatId, $text);
            $this->mss->sendMessage();

            return false;
        }

        return true;
    }

    /**
     * Detects whether a list of tasks contains a milestone.
     *
     * @param array $tasks The list of tasks
     *
     * @return bool True if at least one of the tasks is a milestone. False otherwise.
     */
    private function thereAreMilestones(array $tasks): bool
    {
        foreach ($tasks as $task) {
            if ($task->getIsMilestone()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detects whether a list of tasks contains task related to tracking meetings.
     * For this, a number of key words are searched within the tasks's names.
     *
     * @param array $tasks The list of tasks
     *
     * @return bool True if at least one of the tasks is related to tracking meetings. False otherwise.
     */
    private function thereAreTrackingMeetings(array $tasks): bool
    {
        $keywords = ['meeting', 'reunión', 'reunion', 'tracking', 'seguimiento'];
        foreach ($tasks as $task) {
            foreach ($keywords as $string) {
                // look for tasks containing any of the keywords
                if (false !== strpos(strtolower($task->getName()), $string)) {
                    return true;
                }
            }
        }

        return false;
    }
}
