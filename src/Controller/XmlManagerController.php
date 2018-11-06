<?php

declare(strict_types=1);

namespace MikelAlejoBR\TelegramBotGanttProject\Controller;

use MikelAlejoBR\TelegramBotGanttProject\Entity\Task;
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
    public function deserializeFromFile(string $file_path)
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
}
