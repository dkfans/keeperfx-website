<?php

declare(strict_types=1);

namespace App\Migrations\Production;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240502143819 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'ip_log';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_ip_log (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, ip VARCHAR(255) NOT NULL, first_seen_timestamp DATETIME NOT NULL, last_seen_timestamp DATETIME NOT NULL, country VARCHAR(255) DEFAULT NULL, is_vpn TINYINT(1) DEFAULT NULL, is_tor TINYINT(1) DEFAULT NULL, is_spam TINYINT(1) DEFAULT NULL, host_name VARCHAR(255) DEFAULT NULL, isp VARCHAR(255) DEFAULT NULL, INDEX IDX_E1A9AAE0A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_ip_log ADD CONSTRAINT FK_E1A9AAE0A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_ip_log DROP FOREIGN KEY FK_E1A9AAE0A76ED395');
        $this->addSql('DROP TABLE user_ip_log');
    }
}
