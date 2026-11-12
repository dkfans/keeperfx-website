<?php

declare(strict_types=1);

namespace App\Migrations\Production;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261109120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'crash dump, exit code and replay on crash reports';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE crash_report ADD crash_dump_filename VARCHAR(255) DEFAULT NULL, ADD exit_code VARCHAR(255) DEFAULT NULL, ADD replay_filename VARCHAR(255) DEFAULT NULL, ADD replay_turn INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE crash_report DROP crash_dump_filename, DROP exit_code, DROP replay_filename, DROP replay_turn');
    }
}
