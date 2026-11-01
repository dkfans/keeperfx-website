<?php

declare(strict_types=1);

namespace App\Migrations\Production;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261023070325 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'virustotal status on workshop file';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workshop_file ADD sent_to_virustotal TINYINT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workshop_file DROP sent_to_virustotal');
    }
}
