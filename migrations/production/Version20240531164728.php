<?php

declare(strict_types=1);

namespace App\Migrations\Production;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240531164728 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'email_verification';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_email_verification (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, token VARCHAR(255) NOT NULL, sent TINYINT(1) NOT NULL, created_timestamp DATETIME NOT NULL, UNIQUE INDEX UNIQ_A3A6C5A3A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_email_verification ADD CONSTRAINT FK_A3A6C5A3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user ADD email_verified TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_email_verification DROP FOREIGN KEY FK_A3A6C5A3A76ED395');
        $this->addSql('DROP TABLE user_email_verification');
        $this->addSql('ALTER TABLE user DROP email_verified');
    }
}
