<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use IkastenBot\Exception\IncorrectFileException;
use IkastenBot\Exception\NoMilestonesException;
use IkastenBot\Utils\XmlUtils;
use PHPUnit\Framework\TestCase;

final class XmlUtilsTest extends TestCase
{
    /**
     * Directory path containing test files
     *
     * @var string
     */
    private $data_dir;

    /**
     * Directory containing MSPDI XML files to be imported
     *
     * @var string
     */
    private $xml_dir_mspdi;

    /**
     * Directory containing Gan XML files to be imported
     *
     * @var string
     */
    private $xml_dir_gan;

    /**
     * XML Utils
     *
     * @var XmlUtils
     */
    private $xu;

    public function setUp()
    {
        $this->data_dir        = __DIR__ . '/../_data/xml_milestone_data';
        $this->xml_dir_mspdi    = $this->data_dir . '/mspdi/';
        $this->xml_dir_gan      = $this->data_dir . '/gan/';

        $em = $this->createMock(EntityManager::class);
        $this->xu = new XmlUtils($em);
    }

    public function testExtractNoTasksGanFile()
    {
        $result = $this->xu->extractTasksFromGanFile(
            $this->xml_dir_gan . 'NoTasks.gan',
            12345
        );

        $this->assertEquals(count($result), 0);
    }

    public function testExtractSevenTasksGanFile()
    {
        $result = $this->xu->extractTasksFromGanFile(
            $this->xml_dir_gan . 'SevenTasks.gan',
            12345
        );

        $this->assertEquals(count($result), 7);
    }

    public function testExtractTenTasksGanFile()
    {
        $result = $this->xu->extractTasksFromGanFile(
            $this->xml_dir_gan . 'TenTasks.gan',
            12345
        );

        $this->assertEquals(count($result), 10);
    }

    public function testExtractTwelveTasksGanFile()
    {
        $result = $this->xu->extractTasksFromGanFile(
            $this->xml_dir_gan . 'TwelveTasks.gan',
            12345
        );

        $this->assertEquals(count($result), 12);
    }

    public function testExtractTasksIncorrectFileException()
    {
        $this->expectException(IncorrectFileException::class);

        $this->xu->extractTasksFromGanFile(
            $this->xml_dir_gan . 'incorrectGan.gan',
            12345
        );
    }
}
