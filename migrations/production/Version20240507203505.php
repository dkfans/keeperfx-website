<?php

declare(strict_types=1);

namespace App\Migrations\Production;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;


final class Version20240507203505 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'remove useless ip log host names';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE user_ip_log SET host_name = NULL WHERE ip = host_name');
    }

    public function down(Schema $schema): void
    {
        // Do nothing
    }
}
