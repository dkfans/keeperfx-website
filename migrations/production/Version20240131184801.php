<?php

declare(strict_types=1);

namespace App\Migrations\Production;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240131184801 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'fix broken emails';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE `user` SET email = NULL WHERE email = ''");
    }

    public function down(Schema $schema): void
    {
        // nothing
    }
}
