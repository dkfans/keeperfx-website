<?php

declare(strict_types=1);

namespace App\Migrations\Production;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260816141745 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add commit sha and comment to alpha builds';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE github_alpha_build ADD commit_sha VARCHAR(255) DEFAULT NULL, ADD commit_comment LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE github_alpha_build DROP commit_sha, DROP commit_comment');
    }
}
