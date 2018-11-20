<?php

declare(strict_types=1);

namespace IkastenBot\Utils;

use IkastenBot\Entity\Milestone;
use IkastenBot\Exception\IncorrectFileException;
use IkastenBot\Exception\NoMilestonesException;
use Longman\TelegramBot\DB;

class XmlUtils
{
    public function __construct()
    {
    }

    /**
     * Deserialize a Gan format exported XML file
     *
     * @param   string                  $file_path   The path of the Gan file
     *
     * @return  Milestone[]             Array containing Milestone objects
     *
     * @throws  IncorrectFileException  if the parsing generated any error
     */
    public function deserializeGanFile(string $file_path): array
    {
        \libxml_use_internal_errors(true);

        $data = simplexml_load_file($file_path);

        if (count(\libxml_get_errors())) {
            libxml_clear_errors();
            \libxml_use_internal_errors(false);
            throw new IncorrectFileException('Please send a valid GanttProject Gan file.');
        }

        $xmlMilestones = $data->xpath('//task[@meeting=\'true\']');

        $milestones = [];
        foreach ($xmlMilestones as $xmlMilestone) {
            $milestone = new Milestone();
            $milestone->setName((string)$xmlMilestone->attributes()->name);

            $date = new \DateTime((string)$xmlMilestone->attributes()->start);
            $milestone->setDate($date);

            $milestones[] = $milestone;
        }

        return $milestones;
    }

    /**
     * Deserialize MSPDI format exported XML file
     *
     * @param   string                  $file_path  The path of the XML file
     *
     * @return  Milestone[]             $milestones Array containing Milestone objects
     *
     * @throws  IncorrectFileException  if the parsing generated any error
     */
    public function deserializeMspdiFile(string $file_path): array
    {
        \libxml_use_internal_errors(true);

        $data = simplexml_load_file($file_path);

        if (count(\libxml_get_errors())) {
            libxml_clear_errors();
            \libxml_use_internal_errors(false);
            throw new IncorrectFileException('Please send a valid GanttProject XML file.');
        }

        $data->registerXPathNamespace('project', 'http://schemas.microsoft.com/project');

        $xmlMilestones = $data->xpath('//project:Task[./project:Milestone=\'1\']');

        $milestones = [];
        foreach ($xmlMilestones as $xmlMilestone) {
            $milestone = new Milestone();
            $milestone->setName((string)$xmlMilestone->Name);

            $date = new \DateTime((string)$xmlMilestone->Start);
            $milestone->setDate($date);

            $milestones[] = $milestone;
        }

        return $milestones;
    }

    /**
     * Extract milestones from the XML file and store them in the database
     *
     * @param   string  $file_path      The path to the XML file
     * @param   int     $chat_id        The id of the chat to which the milestones
     *                                  will be assigned to
     *
     * @return  Milestone[]             Array of Milestones
     *
     * @throws  NoMilestonesException   When no milestones have been found in the
     *                                  XML file.
     */
    public function extractStoreMilestones(string $file_path, int $chat_id): array
    {
        $file_info = new \SplFileInfo($file_path);
        $file_extension = $file_info->getExtension();

        $milestones = [];
        if ('gan' === $file_extension) {
            $milestones = $this->deserializeGanFile($file_path);
        } elseif ('xml' === $file_extension) {
            $milestones = $this->deserializeMspdiFile($file_path);
        } else {
            throw new IncorrectFileException('Please send a valid GanttProject or XML MSPDI file.');
        }

        if (empty($milestones)) {
            throw new NoMilestonesException(
                'The provided file doesn\'t contain any milestones. Please send another file.'
            );
        }

        foreach ($milestones as $milestone) {
            $sql = '';
            $parameters = [
                ':chat_id'          => $chat_id,
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
                        :chat_id,
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
