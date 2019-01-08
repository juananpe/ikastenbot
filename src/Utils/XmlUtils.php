<?php

declare(strict_types=1);

namespace IkastenBot\Utils;

use IkastenBot\Entity\Task;
use IkastenBot\Exception\IncorrectFileException;
use IkastenBot\Exception\NoMilestonesException;
use IkastenBot\Exception\NoTasksException;
use Longman\TelegramBot\DB;

class XmlUtils
{
    public function __construct()
    {
    }

    /**
     * Extract tasks from gan file
     *
     * @param   string $file_path   The path of the Gan file
     * @return  Task[]              Array of tasks
     */
    public function extractTasksFromGanFile(string $file_path): array
    {
        \libxml_use_internal_errors(true);

        $data = simplexml_load_file($file_path);

        if (count(\libxml_get_errors())) {
            libxml_clear_errors();
            \libxml_use_internal_errors(false);
            throw new IncorrectFileException('Please send a valid GanttProject Gan file.');
        }

        $xmlTasks = $data->xpath('//task');

        $tasks = [];
        foreach ($xmlTasks as $xmlTask) {
            $task = new Task();
            $task->setName((string)$xmlTask->attributes()->name);

            $date = new \DateTime((string)$xmlTask->attributes()->start);
            $task->setDate($date);
            $task->setIsMilestone(\filter_var($xmlTask->attributes()->meeting, FILTER_VALIDATE_BOOLEAN));
            $task->setDuration((int)$xmlTask->attributes()->duration);

            $tasks[] = $task;
        }

        return $tasks;
    }

    /**
     * Extract tasks from the XML Gan file and store them in the database
     *
     * @param   string  $file_path      The path to the XML Gan file
     * @param   int     $chat_id        The id of the chat to which the tasks
     *                                  will be assigned to
     *
     * @return  Task[]                  Array of Tasks
     *
     * @throws  NoTasksException        When no tasks have been found in the
     *                                  XML file.
     */
    public function extractStoreTasks(string $file_path, int $chat_id): array
    {
        $file_info = new \SplFileInfo($file_path);
        $file_extension = $file_info->getExtension();

        $tasks = [];
        if ('gan' === $file_extension) {
            $tasks = $this->extractTasksFromGanFile($file_path);
        } else {
            throw new IncorrectFileException('Please send a valid GanttProject file.');
        }

        if (empty($tasks)) {
            throw new NoTasksException(
                'The provided file doesn\'t contain any tasks. Please send another file.'
            );
        }

        foreach ($tasks as $task) {
            $sql = '';
            $parameters = [
                ':chat_id'          => $chat_id,
                ':task_date'   => $task->getDate()->format('Y-m-d'),
                ':task_isMilestone' => $task->getIsMilestone(),
                ':task_duration' => $task->getDuration(),
            ];
            $hasName = !empty($task->getName());

            if ($hasName) {
                $sql = '
                    INSERT INTO task(
                        chat_id,
                        task_name,
                        task_date,
                        task_isMilestone,
                        task_duration
                    ) VALUES (
                        :chat_id,
                        :task_name,
                        :task_date,
                        :task_isMilestone,
                        :task_duration
                    );
                ';
                $parameters[':task_name'] = $task->getName();
            } else {
                $sql = '
                    INSERT INTO task(
                        chat_id,
                        task_date,
                        task_isMilestone,
                        task_duration
                    ) VALUES (
                        :chat_id,
                        :task_date,
                        :task_isMilestone,
                        :task_duration
                    );
                ';
            }

            $statement = DB::getPdo()->prepare($sql, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
            $statement->execute($parameters);
        }

        return $tasks;
    }
}
