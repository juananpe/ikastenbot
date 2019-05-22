<?php

namespace App\Service;

class SimilarTasksDurationNotifier
{
    /**
     * @var SimilarTaskFinder
     */
    private $stf;

    /**
     * @var string
     */
    private $chatId;

    /**
     * SimilarTasksDurationNotifier constructor.
     *
     * @param string $chatId
     */
    public function __construct($chatId)
    {
        $this->stf = new SimilarTaskFinder();
        $this->chatId = $chatId;
    }

    /**
     * Analyzes the tasks to find ones with atypical durations
     * and sends a message listing them.
     *
     * @param array $tasks List of tasks
     */
    public function NotifyOfAtypicalTasks($tasks)
    {
        $this->stf = new SimilarTaskFinder();
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
    private function sendMessage($tasksTimes)
    {
        $mss = new MessageSenderService();
        $text = 'Estas tareas divergen mucho en duración del resto de tareas de la base de datos'.PHP_EOL
            .'Eso no significa que sean incorrectas, pero puede ser recomendable echarles un segundo vistazo.'.PHP_EOL
            .'(tarea -> tu duración // duración media estimada)'.PHP_EOL.PHP_EOL;

        foreach ($tasksTimes as $taskInfo) {
            $text .= $taskInfo['taskName'].' -> '
                .$taskInfo['taskDuration'].' // '
                .$taskInfo['avgDuration'].PHP_EOL;
        }

        $mss->prepareMessage($this->chatId, $text);
        $mss->sendMessage();
    }
}
