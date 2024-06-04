<?php

declare(strict_types=1);

namespace App\Migrations\Production;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240604183644 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'fix_email_status';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE `mail` SET `status` = 2 WHERE `status` = 1');
    }

    public function down(Schema $schema): void
    {
        // nothing
    }
}
