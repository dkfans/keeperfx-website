<?php

declare(strict_types=1);

namespace App\Migrations\Production;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260813135713 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'moderator notes on user and workshop item';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD moderator_note LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE workshop_item ADD moderator_note_hidden LONGTEXT DEFAULT NULL, ADD moderator_note_public LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workshop_item DROP moderator_note_hidden, DROP moderator_note_public');
        $this->addSql('ALTER TABLE user DROP moderator_note');
    }
}
