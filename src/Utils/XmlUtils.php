<?php

declare(strict_types=1);

namespace MikelAlejoBR\TelegramBotGanttProject\Utils;

use Longman\TelegramBot\DB;
use Longman\TelegramBot\Entities\Chat;
use MikelAlejoBR\TelegramBotGanttProject\Entity\Milestone;
use MikelAlejoBR\TelegramBotGanttProject\Exception\IncorrectFileException;
use MikelAlejoBR\TelegramBotGanttProject\Exception\NoMilestonesException;

class XmlUtils
{
    public function __construct()
    {
    }

    /**
     * Traverse through XML finding tasks and returns either an empty
     * array or an array of \SimpleXMLElement that are tasks
     *
     * @param   \SimpleXMLElement   $xml    XML task element
     * @param   array               $tasks  Array containing tasks
     * @return  array                       Array containing tasks
     */
    public function recursiveXmlLookup(\SimpleXMLElement $xml, array $tasks): array
    {
        if (!$xml->task) {
            return [$xml];
        }

        $tasks[] = $xml;
        return \array_merge($tasks, $this->recursiveXmlLookup($xml->task, $tasks));
    }

    /**
     * Deserialize a Gan format exported XML file
     *
     * @param   string $file_path   The path of the Gan file
     * @return  array               Array containing Milestone objects
     */
    public function dedeserializeGanFile(string $file_path): array
    {
        $data = simplexml_load_file($file_path);

        $tasks = [];
        foreach ($data->tasks->task as $task) {
            $tasks = \array_merge($tasks, $this->recursiveXmlLookup($task, []));
        }

        $milestones = [];
        foreach ($tasks as $key => $task) {
            if ("true" === (string)$task->attributes()->meeting) {
                $milestone = new Milestone();
                $milestone->setName((string)$task->attributes()->name);

                $date = new \DateTime((string)$task->attributes()->start);
                $milestone->setDate($date);

               $milestones[] = $milestone;
            }
        }

        return $milestones;
    }

    /**
     * Deserialize MSPDI format exported XML file
     *
     * @param   string  $file_path  The path of the XML file
     * @return  array   $milestones Array containing Milestone objects
     */
    public function deserializeMsdpiFile(string $file_path): array
    {
        $data = simplexml_load_file($file_path);

        $milestones = [];
        foreach ($data->Tasks->Task as $task) {
            if ((int)$task->Milestone) {
                $milestone = new Milestone();
                $milestone->setName((string)$task->Name);

                $date = new \DateTime((string)$task->Start);
                $milestone->setDate($date);

                $milestones[] = $milestone;
            }
        }

        return $milestones;
    }

    /**
     * Extract milestones from the XML file and store them in the database
     *
     * @param   string  $file_path The path to the XML file
     * @param   Chat    $chat      The chat to which the milestones will be
     *                             assigned to
     * @return  array              Array of Milestones
     * @throws  NoMilestonesException When no milestones have been found in the
     *                               XML file.
     */
    public function extractStoreMilestones(string $file_path, Chat $chat): array
    {
        $file_info = new \SplFileInfo($file_path);
        $file_extension = $file_info->getExtension();

        $milestones = [];
        if ('gan' === $file_extension) {
            $milestones = $this->dedeserializeGanFile($file_path);
        } elseif ('xml' === $file_extension) {
            $milestones = $this->deserializeMsdpiFile($file_path);
        } else {
            throw new IncorrectFileException(
                'The provided file isn\'t a GanttProject file or an MSPDI XML file'
            );
        }

        if (empty($milestones)) {
            throw new NoMilestonesException(
                'The provided file doesn\'t contain any milestones'
            );
        }

        foreach ($milestones as $milestone) {
            $sql = '';
            $parameters = [
                ':chat_id'          => $chat->getId(),
                ':milestone_date'   => $milestone->getDate()->format('Y-m-d'),
            ];
            $hasName = !empty($milestone->getName());

            if ($hasName) {
                $sql = '
                    INSERT INTO milestone(
                        chat_id,
                        milestone_name,
                        milestone_date
                    ) VALUES (
                        :chat_id,
                        :milestone_name,
                        :milestone_date
                    );
                ';
                $parameters[':milestone_name'] = $milestone->getName();
            } else {
                $sql = '
                    INSERT INTO milestone(
                        chat_id,
                        milestone_date
                    ) VALUES (
                        :chat_id
                        :milestone_date
                    );
                ';
            }

            $statement = DB::getPdo()->prepare($sql, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
            $statement->execute($parameters);
        }

        return $milestones;
    }
}
