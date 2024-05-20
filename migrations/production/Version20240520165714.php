<?php

declare(strict_types=1);

namespace App\Migrations\Production;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240520165714 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'bans';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE ban (id INT AUTO_INCREMENT NOT NULL, type INT NOT NULL, pattern VARCHAR(255) NOT NULL, reason LONGTEXT DEFAULT NULL, created_timestamp DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE ban');
    }
}
