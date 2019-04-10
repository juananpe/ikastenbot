<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190220172401 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Imports base schemas and initial domain —GanttProject, Task and User—';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        // Import php-telegram-bot's base schema file, from the 0.54.1 version
        $this->addSql(\file_get_contents(__DIR__.'/BaseSchemas/schema_ptb_v0541.sql'));
        // Import legacy structure.sql file
        $this->addSql(\file_get_contents(__DIR__.'/BaseSchemas/schema_legacy.sql'));

        // Import model
        $this->addSql('CREATE TABLE task (id INT AUTO_INCREMENT NOT NULL, ganttproject_id INT DEFAULT NULL, gan_id INT NOT NULL, chat_id BIGINT NOT NULL, task_name VARCHAR(255) NOT NULL, task_date DATETIME NOT NULL, task_isMilestone TINYINT(1) NOT NULL, task_duration INT NOT NULL, notify TINYINT(1) NOT NULL, INDEX IDX_527EDB25777B1DBF (ganttproject_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ganttproject (id INT AUTO_INCREMENT NOT NULL, user_id BIGINT DEFAULT NULL COMMENT \'Unique user identifier\', file_name VARCHAR(255) NOT NULL, version INT NOT NULL, INDEX IDX_4EE13CF0A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB25777B1DBF FOREIGN KEY (ganttproject_id) REFERENCES ganttproject (id)');
        $this->addSql('ALTER TABLE ganttproject ADD CONSTRAINT FK_4EE13CF0A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        // Disable foreign key checks as essentially this wipes out the entire database
        $this->addSql('SET FOREIGN_KEY_CHECKS = 0');

        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB25777B1DBF');
        $this->addSql('DROP TABLE task');
        $this->addSql('DROP TABLE ganttproject');

        // Drop tables for the legacy structure.sql
        $this->addSql('DROP TABLE TFG');
        $this->addSql('DROP TABLE TFGversion');
        $this->addSql('DROP TABLE TFGimage');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE special_notification');
        $this->addSql('DROP TABLE system_message');
        $this->addSql('DROP TABLE messages_lang');
        $this->addSql('DROP TABLE faq_question');
        $this->addSql('DROP TABLE faq_response');

        // Drop tables for Longman's structure.sql
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE chat');
        $this->addSql('DROP TABLE user_chat');
        $this->addSql('DROP TABLE inline_query');
        $this->addSql('DROP TABLE chosen_inline_result');
        $this->addSql('DROP TABLE message');
        $this->addSql('DROP TABLE callback_query');
        $this->addSql('DROP TABLE edited_message');
        $this->addSql('DROP TABLE telegram_update');
        $this->addSql('DROP TABLE conversation');
        $this->addSql('DROP TABLE botan_shortener');
        $this->addSql('DROP TABLE request_limiter');

        // Enable foreign key checks
        $this->addSql('SET FOREIGN_KEY_CHECKS = 1');
    }
}
