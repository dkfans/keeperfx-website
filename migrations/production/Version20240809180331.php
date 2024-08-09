<?php

declare(strict_types=1);

namespace App\Migrations\Production;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240809180331 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'user themes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD theme VARCHAR(255) DEFAULT "default" NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP theme');
    }
}
