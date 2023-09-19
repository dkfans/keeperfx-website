<?php

declare(strict_types=1);

namespace App\Migrations\Production;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230919172142 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_notification ADD is_read TINYINT(1) NOT NULL, CHANGE message message VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`');

    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_notification DROP is_read, CHANGE message message VARCHAR(255) NOT NULL');
    }
}
