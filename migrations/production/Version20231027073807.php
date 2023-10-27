<?php

declare(strict_types=1);

namespace App\Migrations\Production;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231027073807 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workshop_item CHANGE updated_timestamp updated_timestamp DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workshop_item CHANGE updated_timestamp updated_timestamp DATETIME NOT NULL');
    }
}
