<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\GanttProject;
use App\Entity\Task;
use App\Service\FilesystemUtilsService;
use App\Service\XmlUtilsService;
use App\Tests\DatabaseTestCase;
use App\Tests\Fixtures\GanttProjectDataLoader;
use App\Tests\Fixtures\SingleTaskDataLoader;
use App\Tests\Fixtures\UserDataLoader;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @covers \App\Service\FilesystemUtilsService
 *
 * @internal
 */
final class FilesystemUtilsServiceDbTest extends DatabaseTestCase
{
    /**
     * Entity manager.
     *
     * @var EntityManager
     */
    private $em;

    /**
     * Filesystem utils.
     *
     * @var FilesystemUtils
     */
    private $fu;

    /**
     * Test GanttProject.
     *
     * @var GanttProject
     */
    private $ganttProject;

    public function setUp(): void
    {
        // Get the entity manager
        $this->em = $this->getEntityManager();

        // Insert a test chat to avoid the foreign key constraints
        $this->insertDummyTestChat();

        // Load fixtures into the database for the tests
        $loader = new Loader();
        $loader->addFixture(new UserDataLoader());
        $loader->addFixture(new GanttProjectDataLoader());
        $loader->addFixture(new SingleTaskDataLoader());

        $purger = new ORMPurger();
        $executor = new ORMExecutor($this->em, $purger);
        $executor->execute($loader->getFixtures());

        // Fetch the test GanttProject
        $this->ganttProject = $this
            ->em
            ->getRepository(GanttProject::class)->find(1);

        // Create the Symfony Filesystem component
        $this->filesystem = new Filesystem();

        // Create the filesystem utils
        $this->fu = new FilesystemUtilsService($this->em, $this->filesystem);

        // Create the XML utils
        $this->xu = new XmlUtilsService($this->em);
    }

    public function tearDown(): void
    {
        $tables = [
            'chat',
            'ganttproject',
            'task',
            'user',
        ];

        $this->truncateTables($tables);
        $this->closeEntityManager();
    }

    /**
     * @covers  \App\Service\FilesystemUtilsService::saveToNewGanFile()
     */
    public function testCreateNewGanFileVersion()
    {
        // Load a test XML file
        $xml = $this->xu->openXmlFile(
            __DIR__.'/../_data/task_data/gan/TwelveTasks.gan'
        );

        // Define the DOWNLOAD_DIR constant required by the FilesystemUtils
        $downloadDir = __DIR__.'/../../files/download';
        if (!\defined('DOWNLOAD_DIR')) {
            define('DOWNLOAD_DIR', $downloadDir);
        }

        $this->fu->saveToNewGanFile($xml, $this->ganttProject);

        // Calculate the new version of the Gan file
        $newVersion = $this->ganttProject->getVersion() + 1;

        // Calculate the directory where the Gan file should be
        $ganFilePath = $downloadDir.'/'.
            $this->ganttProject->getUser()->getId().'/'.
            $newVersion.'/'.
            $this->ganttProject->getFilename();

        // Load the target Gan file
        $ganXml = $this->xu->openXmlFile($ganFilePath);

        // Check that the XML contents are identical
        $this->assertEquals($xml, $ganXml);

        // Remove the directory containing the test files
        $this->filesystem->remove($downloadDir);

        // Check that the new GanttProject was properly created
        $newGanttProject = $this
            ->em
            ->getRepository(GanttProject::class)->find(2);

        $this->assertEquals(
            $this->ganttProject->getFileName(),
            $newGanttProject->getFileName()
        );

        $this->assertEquals(
            2,
            $newGanttProject->getVersion()
        );

        $this->assertEquals(
            $this->ganttProject->getUser(),
            $newGanttProject->getUser()
        );

        // Check that the associated task was properly created

        $task = $this->em->getRepository(Task::class)->find(2);

        $this->assertEquals(
            12345,
            $task->getChat_id()
        );

        $this->assertEquals(
            'Test task',
            $task->getName()
        );

        $this->assertEquals(
            '2021-01-01',
            $task->getDate()->format('Y-m-d')
        );

        $this->assertFalse($task->getIsMilestone());

        $this->assertEquals(
            3,
            $task->getDuration()
        );

        $this->assertSame(
            $newGanttProject,
            $task->getGanttProject()
        );
    }
}
