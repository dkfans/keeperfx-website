<?php

declare(strict_types=1);

namespace App\Migrations\Production;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230919164758 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'user notification settings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_notification_setting (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, is_enabled TINYINT(1) NOT NULL, INDEX IDX_344BE150A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_notification_setting ADD CONSTRAINT FK_344BE150A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_notification_setting DROP FOREIGN KEY FK_344BE150A76ED395');
        $this->addSql('DROP TABLE user_notification_setting');
    }
}
