<?php

declare(strict_types=1);

namespace App\Migrations\Production;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250806170643 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add release mirrors';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE release_mirror (id INT AUTO_INCREMENT NOT NULL, release_id INT DEFAULT NULL, url VARCHAR(255) NOT NULL, INDEX IDX_954561A7B12A727D (release_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE release_mirror DROP FOREIGN KEY FK_954561A7B12A727D');
    }
}
