<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230505180242 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE workshop_image (id INT AUTO_INCREMENT NOT NULL, item_id INT DEFAULT NULL, filename VARCHAR(255) NOT NULL, `order` INT NOT NULL, created_timestamp DATETIME NOT NULL, INDEX IDX_2DBF5745126F525E (item_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE workshop_image ADD CONSTRAINT FK_2DBF5745126F525E FOREIGN KEY (item_id) REFERENCES workshop_item (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE workshop_image DROP FOREIGN KEY FK_2DBF5745126F525E');
        $this->addSql('DROP TABLE workshop_image');
    }
}
