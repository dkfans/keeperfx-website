<?php

declare(strict_types=1);

namespace App\Migrations\Production;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230108105607 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE github_alpha_build (id INT AUTO_INCREMENT NOT NULL, artifact_id INT NOT NULL, name VARCHAR(255) NOT NULL, workflow_title VARCHAR(255) NOT NULL, filename VARCHAR(255) NOT NULL, timestamp DATETIME NOT NULL, size_in_bytes INT NOT NULL, is_available TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE github_release (id INT AUTO_INCREMENT NOT NULL, tag VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, size_in_bytes INT NOT NULL, timestamp DATETIME NOT NULL, download_url VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE news_article (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, author VARCHAR(255) NOT NULL, created_timestamp DATETIME NOT NULL, short_text LONGTEXT NOT NULL, text LONGTEXT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE github_alpha_build');
        $this->addSql('DROP TABLE github_release');
        $this->addSql('DROP TABLE news_article');
    }
}
