<?php

declare(strict_types=1);

namespace App\Migrations\Production;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230601230232 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_oauth_token CHANGE token token VARCHAR(255) DEFAULT NULL, CHANGE refresh_token refresh_token VARCHAR(255) DEFAULT NULL, CHANGE expires_timestamp expires_timestamp DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_oauth_token CHANGE token token VARCHAR(255) NOT NULL, CHANGE refresh_token refresh_token VARCHAR(255) NOT NULL, CHANGE expires_timestamp expires_timestamp DATETIME NOT NULL');
    }
}
