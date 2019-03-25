<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190321115703 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add center, director and when to TFG';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE TFG
			ADD COLUMN `center` INT(11) NULL DEFAULT NULL AFTER `lang`,
			ADD COLUMN `director` BIGINT(20)  NULL DEFAULT NULL AFTER `center`,
			ADD COLUMN `when` DATETIME NULL DEFAULT NOW() AFTER `director`');

        $this->addSql("INSERT INTO `system_message` (`tag`, `es`) VALUES ('registerTFGChooseATeacher', '¿Quién es tu director/a?')");
        $this->addSql("INSERT INTO `system_message` (`tag`, `es`) VALUES ('registerTFGChooseACenter', '¿Cuál es tu centro?')");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('ALTER TABLE `TFG` 
			DROP COLUMN `when`,
			DROP COLUMN `director`,
			DROP COLUMN `center`');
        $this->addSql("DELETE FROM `system_message` WHERE (`tag` = 'registerTFGChooseACenter')");
        $this->addSql("DELETE FROM `system_message` WHERE (`tag` = 'registerTFGChooseATeacher')");
    }
}
