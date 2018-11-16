<?php

declare(strict_types=1);

use Longman\TelegramBot\Entities\Chat;
use PHPUnit\Framework\TestCase;
use TelegramBotGanttProject\Exception\IncorrectFileException;
use TelegramBotGanttProject\Exception\NoMilestonesException;
use TelegramBotGanttProject\Utils\XmlUtils;

final class XmlUtilsTest extends TestCase
{
    /**
     * Directory path containing test files
     *
     * @var string
     */
    private $files_dir;

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
        $this->files_dir        = __DIR__ . '/../_files/xml_milestone_files';
        $this->xml_dir_mspdi    = $this->files_dir . '/mspdi/';
        $this->xml_dir_gan      = $this->files_dir . '/gan/';
        $this->xu = new XmlUtils();
    }

    public function testZeroGanDeserializedMilestones()
    {
        $result = $this->xu->deserializeGanFile(
            $this->xml_dir_gan . 'NoMilestones.gan'
        );

        $this->assertEquals(count($result), 0);
    }

    public function testThreeGanDeserializedMilestones()
    {
        $result = $this->xu->deserializeGanFile(
            $this->xml_dir_gan . 'ThreeMilestones.gan'
        );
        
        $this->assertEquals(count($result), 3);
    }

    public function testFiveGanDeserializedMilestones()
    {
        $result = $this->xu->deserializeGanFile(
            $this->xml_dir_gan . 'FiveMilestones.gan'
        );
        
        $this->assertEquals(count($result), 5);
    }

    public function testZeroMspdiDeserializedMilestones()
    {
        $result = $this->xu->deserializeMspdiFile(
            $this->xml_dir_mspdi . 'NoMilestones.xml'
        );

        $this->assertEquals(count($result), 0);
    }

    public function testThreeMspdiDeserializedMilestones()
    {
        $result = $this->xu->deserializeMspdiFile(
            $this->xml_dir_mspdi . 'ThreeMilestones.xml'
        );
        
        $this->assertEquals(count($result), 3);
    }

    public function testFiveMspdiDeserializedMilestones()
    {
        $result = $this->xu->deserializeMspdiFile(
            $this->xml_dir_mspdi . 'FiveMilestones.xml'
        );

        $this->assertEquals(count($result), 5);
    }

    public function testIncorrectFileException()
    {
        $chat = $this->createMock(Chat::class);

        $this->expectException(IncorrectFileException::class);
        
        $this->xu->extractStoreMilestones(
            $this->files_dir . 'incorrectFile.txt',
            12345
        );
    }

    public function testNoMilestonesException()
    {
        $chat = $this->createMock(Chat::class);

        $this->expectException(NoMilestonesException::class);

        $this->xu->extractStoreMilestones(
            $this->xml_dir_gan . 'NoMilestones.gan',
            12345
        );
    }
}
