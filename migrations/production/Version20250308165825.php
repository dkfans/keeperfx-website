<?php

declare(strict_types=1);

namespace App\Migrations\Production;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250308165825 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add game file index';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE game_file_index (id INT AUTO_INCREMENT NOT NULL, release_type VARCHAR(255) NOT NULL, version VARCHAR(255) NOT NULL, data LONGTEXT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE game_file_index');
    }
}
