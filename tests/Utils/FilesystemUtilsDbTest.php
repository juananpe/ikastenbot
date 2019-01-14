<?php

declare(strict_types=1);

use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManager;
use IkastenBot\Entity\GanttProject;
use IkastenBot\Entity\Task;
use IkastenBot\Tests\DatabaseTestCase;
use IkastenBot\Tests\Fixtures\GanttProjectDataLoader;
use IkastenBot\Tests\Fixtures\UserDataLoader;
use IkastenBot\Tests\Fixtures\SingleTaskDataLoader;
use IkastenBot\Utils\FilesystemUtils;
use IkastenBot\Utils\XmlUtils;
use Symfony\Component\Filesystem\Filesystem;

final class FilesystemUtilsDbTest extends DatabaseTestCase
{

    /**
     * Entity manager
     *
     * @var EntityManager
     */
    private $em;

    /**
     * Filesystem utils
     *
     * @var FilesystemUtils
     */
    private $fu;

    /**
     * Test GanttProject
     *
     * @var GanttProject
     */
    private $ganttProject;

    public function setUp(): void
    {
        // Get the entity manager
        $this->connection = $this->getConnection();
        $this->pdo = $this->connection->getConnection();
        $this->em = $this->getDoctrineEntityManager();

        // Clear the tables just in case any test didn't truncate the tables
        $this->tearDown();

        // Insert a test chat to avoid the foreign key constraints
        $insert_test_chat = 'INSERT INTO chat (id) VALUES (12345)';
        $statement = $this->pdo->prepare($insert_test_chat);
        $statement->execute();

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
        $this->fu = new FilesystemUtils($this->em, $this->filesystem);

        // Create the XML utils
        $this->xu = new XmlUtils($this->em);
    }

    public function tearDown(): void
    {
        $connection = $this->em->getConnection();
        $platform   = $connection->getDatabasePlatform();

        $connection->executeQuery('SET FOREIGN_KEY_CHECKS = 0');

        $truncate = $platform->getTruncateTableSQL('chat');
        $connection->executeUpdate($truncate);

        $truncate = $platform->getTruncateTableSQL('user');
        $connection->executeUpdate($truncate);

        $truncate = $platform->getTruncateTableSQL('ganttproject');
        $connection->executeUpdate($truncate);

        $truncate = $platform->getTruncateTableSQL('task');
        $connection->executeUpdate($truncate);

        $connection->executeQuery('SET FOREIGN_KEY_CHECKS = 1');
    }

    public function testCreateNewGanFileVersion()
    {
        // Load a test XML file
        $xml = $this->xu->openXmlFile(
            __DIR__ . '/../_data/xml_milestone_data/gan/TwelveTasks.gan'
        );

        $this->fu->saveToNewGanFile($xml, $this->ganttProject);

        // Calculate the new version of the Gan file
        $newVersion = $this->ganttProject->getVersion() + 1;

        // Calculate the directory where the Gan file should be
        $ganFilePath = DOWNLOAD_DIR . '/' .
            $this->ganttProject->getUser()->getId() . '/' .
            $newVersion . '/' .
            $this->ganttProject->getFilename();

        // Load the target Gan file
        $ganXml = $this->xu->openXmlFile($ganFilePath);

        // Check that the XML contents are identical
        $this->assertEquals($xml, $ganXml);

        // Remove the directory containing the test files
        $this->filesystem->remove(DOWNLOAD_DIR);

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
