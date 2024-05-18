<?php

declare(strict_types=1);

namespace App\Migrations\Production;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240518020229 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'user bio';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_bio (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, bio LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, updated_timestamp DATETIME NOT NULL, UNIQUE INDEX UNIQ_36360BF0A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_bio ADD CONSTRAINT FK_36360BF0A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_bio DROP FOREIGN KEY FK_36360BF0A76ED395');
        $this->addSql('DROP TABLE user_bio');
    }
}
