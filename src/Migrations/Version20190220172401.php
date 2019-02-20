<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190220172401 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'First migration of the domain';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE task (id INT AUTO_INCREMENT NOT NULL, ganttproject_id INT DEFAULT NULL, gan_id INT NOT NULL, chat_id BIGINT NOT NULL, task_name VARCHAR(255) NOT NULL, task_date DATETIME NOT NULL, task_isMilestone TINYINT(1) NOT NULL, task_duration INT NOT NULL, notify TINYINT(1) NOT NULL, INDEX IDX_527EDB25777B1DBF (ganttproject_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ganttproject (id INT AUTO_INCREMENT NOT NULL, user_id BIGINT DEFAULT NULL COMMENT \'Unique user identifier\', file_name VARCHAR(255) NOT NULL, version INT NOT NULL, INDEX IDX_4EE13CF0A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB25777B1DBF FOREIGN KEY (ganttproject_id) REFERENCES ganttproject (id)');
        $this->addSql('ALTER TABLE ganttproject ADD CONSTRAINT FK_4EE13CF0A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB25777B1DBF');
        $this->addSql('DROP TABLE task');
        $this->addSql('DROP TABLE ganttproject');
    }
}
