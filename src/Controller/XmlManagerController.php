<?php

declare(strict_types=1);

namespace MikelAlejoBR\TelegramBotGanttProject\Controller;

use Longman\TelegramBot\DB;
use Longman\TelegramBot\Entities\User;
use MikelAlejoBR\TelegramBotGanttProject\Entity\Task;
use MikelAlejoBR\TelegramBotGanttProject\Exception\NoMilestonesException;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

class XmlManagerController
{
    public function __construct()
    {
    }

    /**
     * Deserialize GanntProjects' XML file
     *
     * @param   string  $file_path The path of the XML file
     * @return  array   $tasks     Array containing Task objects which are
     *                             milestones
     */
    public function deserializeFromFile(string $file_path): array
    {
        $encoder = array(new XmlEncoder());
        $normalizers = array(
            new DateTimeNormalizer(),
            new ObjectNormalizer(null, null, null, new ReflectionExtractor())
        );

        $serializer = new Serializer($normalizers, $encoder);

        $data = simplexml_load_file($file_path);

        $tasks = [];
        foreach ($data->Tasks->Task as $task) {
            if ((int)$task->Milestone) {
                $tasks[] = $serializer->deserialize($task->asXML(), Task::class, 'xml');
            }
        }

        return $tasks;
    }

    /**
     * Extract milestones from the XML file and store them in the database
     *
     * @param   string  $file_path The path to the XML file
     * @param   User    $user      The User to which the milestones will be
     *                             assigned to
     * @return  array              Array of Tasks
     * @throws  NoMilestonesException When no milestones have been found in the
     *                               XML file.
     */
    public function extractStoreTasks(string $file_path, User $user): array
    {
        $tasks = $this->deserializeFromFile($file_path);
        if (empty($tasks)) {
            throw new NoMilestonesException(
                'The provided file doesn\'t contain any milestones'
            );
        }

        foreach ($tasks as $task) {
            $sql = '';
            $parameters = [
                ':user_id'                  => $user->getId(),
                ':milestone_start_date'     => $task->getStart()->format('Y-m-d H:i:s'),
                ':milestone_finish_date'    => $task->getFinish()->format('Y-m-d H:i:s')
            ];
            $hasName = !empty($task->getName());

            if ($hasName) {
                $sql = '
                    INSERT INTO milestone(
                        user_id,
                        milestone_name,
                        milestone_start_date,
                        milestone_finish_date
                    ) VALUES (
                        :user_id,
                        :milestone_name,
                        :milestone_start_date,
                        :milestone_finish_date
                    );
                ';
                $parameters[':milestone_name'] = $task->getName();
            } else {
                $sql = '
                    INSERT INTO milestone(
                        user_id,
                        milestone_start_date,
                        milestone_finish_date
                    ) VALUES (
                        :user_id
                        :milestone_start_date,
                        :milestone_finish_date
                    );
                ';
            }

            $statement = DB::getPdo()->prepare($sql, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
            $statement->execute($parameters);
        }

        return $tasks;
    }
}
