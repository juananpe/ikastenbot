<?php

declare(strict_types=1);

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
        $this->xu = new XmlUtils();
    }

    public function testZeroGanDeserializedMilestones()
    {
        $result = $this->xu->extractMilestonesFromGanFile(
            $this->xml_dir_gan . 'NoMilestones.gan'
        );

        $this->assertEquals(count($result), 0);
    }

    public function testThreeGanDeserializedMilestones()
    {
        $result = $this->xu->extractMilestonesFromGanFile(
            $this->xml_dir_gan . 'ThreeMilestones.gan'
        );

        $this->assertEquals(count($result), 3);
    }

    public function testFiveGanDeserializedMilestones()
    {
        $result = $this->xu->extractMilestonesFromGanFile(
            $this->xml_dir_gan . 'FiveMilestones.gan'
        );

        $this->assertEquals(count($result), 5);
    }

    public function testZeroMspdiDeserializedMilestones()
    {
        $result = $this->xu->extractMilestonesFromMspdiFile(
            $this->xml_dir_mspdi . 'NoMilestones.xml'
        );

        $this->assertEquals(count($result), 0);
    }

    public function testThreeMspdiDeserializedMilestones()
    {
        $result = $this->xu->extractMilestonesFromMspdiFile(
            $this->xml_dir_mspdi . 'ThreeMilestones.xml'
        );

        $this->assertEquals(count($result), 3);
    }

    public function testFiveMspdiDeserializedMilestones()
    {
        $result = $this->xu->extractMilestonesFromMspdiFile(
            $this->xml_dir_mspdi . 'FiveMilestones.xml'
        );

        $this->assertEquals(count($result), 5);
    }

    public function testIncorrectFileException()
    {
        $this->expectException(IncorrectFileException::class);

        $this->xu->extractStoreMilestones(
            $this->data_dir . 'incorrectFile.txt',
            12345
        );
    }

    public function testNoMilestonesException()
    {
        $this->expectException(NoMilestonesException::class);

        $this->xu->extractStoreMilestones(
            $this->xml_dir_gan . 'NoMilestones.gan',
            12345
        );
    }

    public function testInvalidXmlFile()
    {
        $this->expectException(IncorrectFileException::class);

        $this->xu->extractStoreMilestones(
            $this->data_dir . '/incorrectXml.xml',
            12345
        );
    }

    public function testInvalidGanFile()
    {
        $this->expectException(IncorrectFileException::class);

        $this->xu->extractStoreMilestones(
            $this->data_dir . '/incorrectGan.gan',
            12345
        );
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
