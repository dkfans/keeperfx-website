<?php

declare(strict_types=1);

namespace App\Migrations\Production;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231006164941 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // fix original creation dates that were set by a bug
        $this->addSql('UPDATE workshop_item SET original_creation_date = NULL WHERE original_creation_date > DATE_SUB(created_timestamp, INTERVAL 1 MONTH);');
    }

    public function down(Schema $schema): void
    {
        // nothing
    }
}
