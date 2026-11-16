<?php

declare(strict_types=1);

namespace App\Migrations\Production;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261109120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'linux patch file on alpha builds';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE github_alpha_build ADD linux_filename VARCHAR(255) DEFAULT NULL, ADD linux_size_in_bytes INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE github_alpha_build DROP linux_filename, DROP linux_size_in_bytes');
    }
}
