<?php

declare(strict_types=1);

namespace App\Migrations\Production;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240505235409 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'ip log: proxy/hosting';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_ip_log ADD is_proxy TINYINT(1) DEFAULT NULL, ADD is_hosting TINYINT(1) DEFAULT NULL, DROP is_vpn, DROP is_tor, DROP is_spam');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_ip_log ADD is_vpn TINYINT(1) DEFAULT NULL, ADD is_tor TINYINT(1) DEFAULT NULL, ADD is_spam TINYINT(1) DEFAULT NULL, DROP is_proxy, DROP is_hosting');
    }
}
