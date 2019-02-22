<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Exception\IncorrectFileException;
use App\Service\XmlUtilsService;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class XmlUtilsTest extends TestCase
{
    /**
     * Directory path containing test files.
     *
     * @var string
     */
    private $dataDir;

    /**
     * Directory containing Gan XML files to be imported.
     *
     * @var string
     */
    private $ganDir;

    /**
     * XML Utils.
     *
     * @var XmlUtils
     */
    private $xu;

    public function setUp()
    {
        $this->dataDir = __DIR__.'/../_data/task_data';
        $this->ganDir = $this->dataDir.'/gan/';

        $em = $this->createMock(EntityManager::class);
        $this->xu = new XmlUtilsService($em);
    }

    public function testExtractNoTasksGanFile()
    {
        $result = $this->xu->extractTasksFromGanFile(
            $this->ganDir.'NoTasks.gan',
            12345
        );

        $this->assertEquals(count($result), 0);
    }

    public function testExtractSevenTasksGanFile()
    {
        $result = $this->xu->extractTasksFromGanFile(
            $this->ganDir.'SevenTasks.gan',
            12345
        );

        $this->assertEquals(count($result), 7);
    }

    public function testExtractTenTasksGanFile()
    {
        $result = $this->xu->extractTasksFromGanFile(
            $this->ganDir.'TenTasks.gan',
            12345
        );

        $this->assertEquals(count($result), 10);
    }

    public function testExtractTwelveTasksGanFile()
    {
        $result = $this->xu->extractTasksFromGanFile(
            $this->ganDir.'TwelveTasks.gan',
            12345
        );

        $this->assertEquals(count($result), 12);
    }

    public function testExtractTasksIncorrectFileException()
    {
        $this->expectException(IncorrectFileException::class);

        $this->xu->extractTasksFromGanFile(
            $this->ganDir.'incorrectGan.gan',
            12345
        );
    }
}
