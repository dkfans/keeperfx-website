<?php

declare(strict_types=1);

namespace App\Migrations\Production;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230526134725 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE workshop_item DROP FOREIGN KEY FK_259C0C5921AD222A');
        $this->addSql('DROP INDEX IDX_259C0C5921AD222A ON workshop_item');
        $this->addSql('ALTER TABLE workshop_item CHANGE min_game_build_id min_game_build INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE workshop_item CHANGE min_game_build min_game_build_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE workshop_item ADD CONSTRAINT FK_259C0C5921AD222A FOREIGN KEY (min_game_build_id) REFERENCES github_release (id)');
        $this->addSql('CREATE INDEX IDX_259C0C5921AD222A ON workshop_item (min_game_build_id)');
    }
}
