<?php

declare(strict_types=1);

namespace App\Migrations\Production;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260323124201 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'delete comments when deleting workshop item';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workshop_comment DROP FOREIGN KEY FK_D63B407B126F525E');
        $this->addSql('ALTER TABLE workshop_comment CHANGE item_id item_id INT NOT NULL');
        $this->addSql('ALTER TABLE workshop_comment ADD CONSTRAINT FK_D63B407B126F525E FOREIGN KEY (item_id) REFERENCES workshop_item (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workshop_comment DROP FOREIGN KEY FK_D63B407B126F525E');
        $this->addSql('ALTER TABLE workshop_comment CHANGE item_id item_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE workshop_comment ADD CONSTRAINT FK_D63B407B126F525E FOREIGN KEY (item_id) REFERENCES workshop_item (id)');
    }
}
