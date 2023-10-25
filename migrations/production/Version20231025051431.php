<?php

declare(strict_types=1);

namespace App\Migrations\Production;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231025051431 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE workshop_comment_report (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, comment_id INT DEFAULT NULL, reason LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created_timestamp DATETIME NOT NULL, INDEX IDX_9E57168A76ED395 (user_id), INDEX IDX_9E57168F8697D13 (comment_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE workshop_comment_report ADD CONSTRAINT FK_9E57168A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE workshop_comment_report ADD CONSTRAINT FK_9E57168F8697D13 FOREIGN KEY (comment_id) REFERENCES workshop_comment (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workshop_comment_report DROP FOREIGN KEY FK_9E57168A76ED395');
        $this->addSql('ALTER TABLE workshop_comment_report DROP FOREIGN KEY FK_9E57168F8697D13');
        $this->addSql('DROP TABLE workshop_comment_report');
    }
}
