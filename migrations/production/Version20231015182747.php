<?php

declare(strict_types=1);

namespace App\Migrations\Production;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231015182747 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_notification CHANGE data data VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE user_notification_setting ADD email_enabled TINYINT(1) NOT NULL, CHANGE is_enabled website_enabled TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_notification CHANGE data data VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user_notification_setting ADD is_enabled TINYINT(1) NOT NULL, DROP website_enabled, DROP email_enabled');
    }
}
