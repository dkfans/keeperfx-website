<?php

declare(strict_types=1);

namespace App\Migrations\Production;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250410134911 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'launcher releases';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE launcher_release (id INT AUTO_INCREMENT NOT NULL, tag VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, size_in_bytes INT NOT NULL, `timestamp` DATETIME NOT NULL, is_available TINYINT(1) NOT NULL, PRIMARY KEY(id))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE launcher_release');
    }
}
