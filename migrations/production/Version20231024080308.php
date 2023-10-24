<?php

declare(strict_types=1);

namespace App\Migrations\Production;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;


final class Version20231024080308 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'workshop comments can have parents';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workshop_comment ADD parent_id INT DEFAULT NULL, CHANGE content content LONGTEXT NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE workshop_comment ADD CONSTRAINT FK_D63B407B727ACA70 FOREIGN KEY (parent_id) REFERENCES workshop_comment (id)');
        $this->addSql('CREATE INDEX IDX_D63B407B727ACA70 ON workshop_comment (parent_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workshop_comment DROP FOREIGN KEY FK_D63B407B727ACA70');
        $this->addSql('DROP INDEX IDX_D63B407B727ACA70 ON workshop_comment');
        $this->addSql('ALTER TABLE workshop_comment DROP parent_id, CHANGE content content LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
