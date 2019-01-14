<?php

declare(strict_types=1);

namespace IkastenBot\Utils;

use Doctrine\ORM\EntityManager;
use IkastenBot\Entity\GanttProject;
use IkastenBot\Entity\Task;
use IkastenBot\Exception\IncorrectFileException;
use IkastenBot\Exception\NoMilestonesException;
use IkastenBot\Exception\NoTasksException;
use Longman\TelegramBot\DB;

class XmlUtils
{
    /**
     * Entity manager
     *
     * @var EntityManager
     */
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Opens the XML file of the provided path and throws and exception if
     * any errors occur.
     *
     * @param   string $xmlFilePath     The path of the XML file
     *
     * @return  \SimpleXmlElement       The XML contents of the file
     *
     * @throws IncorrectFileException   if any errors occur during the opening
     *                                  or the parsing of the file
     */
    public function openXmlFile(string $xmlFilePath): \SimpleXmlElement
    {
        \libxml_use_internal_errors(true);

        $xml = simplexml_load_file($xmlFilePath);

        if (count(\libxml_get_errors())) {
            libxml_clear_errors();
            \libxml_use_internal_errors(false);
            throw new IncorrectFileException('The provided file contains invalid XML');
        }

        return $xml;
    }

    /**
     * Extract tasks from gan file
     *
     * @param   string $file_path   The path of the Gan file
     * @return  Task[]              Array of tasks
     */
    public function extractTasksFromGanFile(string $file_path): array
    {
        $data= $this->openXmlFile($file_path);

        $xmlTasks = $data->xpath('//task');

        $tasks = [];
        foreach ($xmlTasks as $xmlTask) {
            $task = new Task();
            $task->setName((string)$xmlTask->attributes()->name);

            $date = new \DateTime((string)$xmlTask->attributes()->start);
            $task->setGanId((int)$xmlTask->attributes()->id);
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
    public function extractStoreTasks(string $file_path, int $chat_id, GanttProject $ganttProject): array
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
            $task->setChat_id((string)$chat_id);
            $task->setGanttProject($ganttProject);

            $this->em->persist($task);
        }

        $this->em->flush();

        return $tasks;
    }

    /**
     * Finds if the provided XML has a nested task or depend element, and
     * stores the corresponding Task in the provided array.
     *
     * @param Task[]            &$taskPool  The array to which the task
     *                                      will be pushed
     * @param \SimpleXmlElement $xml        The XML to be parsed
     * @param boolean $findTask             True for finding a task element,
     *                                      false for finding a depend element
     * @return void
     */
    public function findNestedTaskOrDepend(array &$taskPool, \SimpleXmlElement $xml, bool $findTask): void
    {
        $xpathQuery = './';

        if ($findTask) {
            $xpathQuery .= 'task';
        } else {
            $xpathQuery .= 'depend';
        }

        $hasNested = $xml->xpath($xpathQuery);
        if ($hasNested) {
            foreach ($hasNested as $xmlElement) {
                $tmpTask = $this->em->getRepository(Task::class)->findOneBy(
                    array(
                        'ganId' => $xmlElement->attributes()->id
                    )
                );
                $taskPool[] = $tmpTask;
            }
        }
    }

    /**
     * Delays the task and any nested or dependant tasks for the amount of
     * days specified. The tasks are updated in the database and in the
     * provided XML.
     *
     * @param   string              $ganFilePath The path to the Gan file
     * @param   Task                $task        The task from which to begin
     *                                           iterating
     * @param   integer             $delay       The delay —in days— to be
     *                                           applied
     * @return  \SimpleXmlElement
     */
    public function delayTaskAndDependants(string $ganFilePath, Task $task, int $delay): \SimpleXmlElement
    {
        $xml = $this->openXmlFile($ganFilePath);

        /**
         * The first task's duration is adjusted to sum the delay specified by
         * the user. The dependant or nested tasks only need to change their
         * start date, as we assume that those haven't been started yet.
         */
        $task->setDuration(
            $task->getDuration() + $delay
        );

        $xmlTask = $xml->xpath('//task[@id="' . $task->getGanId() . '"]')[0];
        $xmlTask->attributes()->duration = $task->getDuration();

        /**
         * Delay the date of the tasks and save them both in the database and
         * in the XML
         */
        $taskPool = [];
        $this->findNestedTaskOrDepend($taskPool, $xmlTask, true);
        $this->findNestedTaskOrDepend($taskPool, $xmlTask, false);
        while (!empty($taskPool)) {
            $tmpTask = \array_shift($taskPool);
            $tmpTask->delayDate($delay);

            $this->em->persist($tmpTask);

            $xmlTask = $xml->xpath('//task[@id="' . $tmpTask->getGanId() . '"]')[0];

            $xmlTask->attributes()->start = $tmpTask->getDate()->format('Y-m-d');

            $this->findNestedTaskOrDepend($taskPool, $xmlTask, true);
            $this->findNestedTaskOrDepend($taskPool, $xmlTask, false);
        }

        $this->em->flush();

        return $xml;
    }
}
