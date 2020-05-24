<?php

declare(strict_types=1);

namespace App\Service;

class SimilarTasksDurationNotifier
{
    /**
     * @var SimilarTaskFinder
     */
    private $stf;

    /**
     * @var int
     */
    private $chatId;

    /**
     * @var MessageSenderService
     */
    private $mss;

    /**
     * SimilarTasksDurationNotifier constructor.
     *
     * @param int               $chatId Id of the chat to send
     *                                  the message to
     * @param SimilarTaskFinder $stf
     */
    public function __construct(int $chatId, SimilarTaskFinder $stf, MessageSenderService $mss)
    {
        $this->stf = $stf;

        $this->chatId = $chatId;

        $this->mss = $mss;
    }

    /**
     * Analyzes the tasks to find ones with atypical durations
     * and sends a message listing them.
     *
     * @param array $tasks List of tasks
     */
    public function notifyOfAtypicalTasks(array $tasks)
    {
        $tasksTimes = $this->stf->getTasksWithAtypicalDuration($tasks);
        if (!empty($tasksTimes)) {
            $this->sendMessage($tasksTimes);
        }
    }

    /**
     * Sends a message listing the tasks, their durations and the
     * average expected duration.
     *
     * @param array $tasksTimes List of tasks
     */
    private function sendMessage(array $tasksTimes)
    {
        $text = 'Estas tareas divergen mucho en duración del resto de tareas de la base de datos'.PHP_EOL
            .'Eso no significa que sean incorrectas, pero puede ser recomendable echarles un segundo vistazo.'.PHP_EOL
            .'(tarea -> tu duración // duración media estimada)'.PHP_EOL.PHP_EOL;

        foreach ($tasksTimes as $taskInfo) {
            $text .= $taskInfo['taskName'].' -> '
                .$taskInfo['taskDuration'].' // '
                .$taskInfo['avgDuration'].PHP_EOL;
        }

        $this->mss->prepareMessage($this->chatId, $text);
        $this->mss->sendMessage();
    }
}