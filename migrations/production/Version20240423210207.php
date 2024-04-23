<?php

declare(strict_types=1);

namespace App\Migrations\Production;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240423210207 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE github_release ADD linked_news_post_id INT DEFAULT NULL, CHANGE name name VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE github_release ADD CONSTRAINT FK_914E9B99B2545A90 FOREIGN KEY (linked_news_post_id) REFERENCES news_article (id)');
        $this->addSql('CREATE INDEX IDX_914E9B99B2545A90 ON github_release (linked_news_post_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE github_release DROP FOREIGN KEY FK_914E9B99B2545A90');
        $this->addSql('DROP INDEX IDX_914E9B99B2545A90 ON github_release');
        $this->addSql('ALTER TABLE github_release DROP linked_news_post_id, CHANGE name name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
