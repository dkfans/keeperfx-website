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

        // fix some last updated timestamps
        $this->addSql('UPDATE workshop_item SET updated_timestamp = NULL WHERE updated_timestamp > DATE_SUB(created_timestamp, INTERVAL 3 DAY);');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workshop_item CHANGE updated_timestamp updated_timestamp DATETIME NOT NULL');
    }
}
