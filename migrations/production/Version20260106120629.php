<?php

declare(strict_types=1);

namespace App\Migrations\Production;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260106120629 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'exception_source_function';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE crash_report ADD exception_source_function VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE crash_report DROP exception_source_function');
    }
}
