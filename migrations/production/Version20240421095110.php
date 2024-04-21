<?php

declare(strict_types=1);

namespace App\Migrations\Production;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240421095110 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add broken workshop file';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE workshop_broken_file (id INT AUTO_INCREMENT NOT NULL, original_item_id INT DEFAULT NULL, original_filename VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, hash VARCHAR(255) NOT NULL, created_timestamp DATETIME NOT NULL, INDEX IDX_5E6FEE1894EFFCA9 (original_item_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE workshop_broken_file ADD CONSTRAINT FK_5E6FEE1894EFFCA9 FOREIGN KEY (original_item_id) REFERENCES workshop_item (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE workshop_file ADD is_broken TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE workshop_item ADD is_last_file_broken TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE workshop_broken_file DROP FOREIGN KEY FK_5E6FEE1894EFFCA9');
        $this->addSql('DROP TABLE workshop_broken_file');
        $this->addSql('ALTER TABLE workshop_file DROP is_broken');
        $this->addSql('ALTER TABLE workshop_item DROP is_last_file_broken');
    }
}
