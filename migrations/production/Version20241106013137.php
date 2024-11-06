<?php

declare(strict_types=1);

namespace App\Migrations\Production;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241106013137 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'fix artifact id number';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE github_alpha_build CHANGE artifact_id artifact_id BIGINT DEFAULT NULL');
        $this->addSql('ALTER TABLE github_prototype CHANGE artifact_id artifact_id BIGINT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE github_alpha_build CHANGE artifact_id artifact_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE github_prototype CHANGE artifact_id artifact_id INT DEFAULT NULL');
    }
}
