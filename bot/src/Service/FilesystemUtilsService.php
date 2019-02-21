<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\GanttProject;
use App\Entity\Task;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Filesystem\Filesystem;

class FilesystemUtilsService
{
    /**
     * Entity manager.
     *
     * @var EntityManager
     */
    protected $em;

    /**
     * Filesystem component.
     *
     * @var Filesystem
     */
    protected $filesystem;

    public function __construct(EntityManager $em, Filesystem $filesystem)
    {
        $this->em = $em;
        $this->filesystem = $filesystem;
    }

    /**
     * Saves the provided XML as a new version of the GanttProject file.
     *
     * @param \SimpleXmlElement $xml          The XML to be saved
     * @param GanttProject      $ganttProject The GanttProject from which
     *                                        information will be extracted
     *                                        for directory and file creation
     *
     * @return string The path to the new Gan file
     */
    public function saveToNewGanFile(\SimpleXmlElement $xml, GanttProject $ganttProject): string
    {
        // Create a new GanttProject from the previous one, updating the version
        $newGanttProject = new GanttProject();
        $newGanttProject->setFileName($ganttProject->getFileName());
        $newGanttProject->setVersion($ganttProject->getVersion() + 1);
        $newGanttProject->setUser($ganttProject->getUser());

        $this->em->persist($newGanttProject);

        // Change the tasks' GanttProject to the new one
        foreach ($ganttProject->getTasks() as $task) {
            $newTask = new Task();
            $newTask->setGanId($task->getGanId());
            $newTask->setChat_id($task->getChat_id());
            $newTask->setName($task->getName());
            $newTask->setDate($task->getDate());
            $newTask->setIsMilestone($task->getIsMilestone());
            $newTask->setDuration($task->getDuration());
            /*
             * When a new GanttProject is created, the notify flag is enabled
             * by default
             */
            $newTask->setNotify(true);
            $newTask->setGanttProject($newGanttProject);

            $this->em->persist($newTask);

            /*
             * Notifications for the older GanttProject's tasks are disabled to
             * avoid getting inaccurate or duplicated notifications
             */
            $task->setNotify(false);

            $this->em->persist($task);
        }

        $this->em->flush();

        // Calculate the paths
        $userDir = DOWNLOAD_DIR.'/'.$newGanttProject->getUser()->getId();
        $newVersionDir = $userDir.'/'.$newGanttProject->getVersion();

        // Create directory and save the gan file
        $this->filesystem->mkdir(
            $userDir.'/'.$newGanttProject->getVersion()
        );

        // FIXME
        $this->filesystem->chmod(
            $userDir.'/'.$newGanttProject->getVersion(),
            0777
        );

        $newVersionGanFile = $newVersionDir.'/'.$newGanttProject->getFileName();

        $xml->asXml($newVersionGanFile);

        return $newVersionGanFile;
    }
}
