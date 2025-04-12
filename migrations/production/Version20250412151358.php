<?php

declare(strict_types=1);

namespace App\Migrations\Production;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250412151358 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'crash report game_config and contact_details';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE crash_report ADD game_config LONGTEXT NOT NULL, ADD contact_details VARCHAR(255), CHANGE description description LONGTEXT NOT NULL COLLATE `utf8mb4_unicode_ci`');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE crash_report DROP game_config, DROP contact_details, CHANGE description description LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
