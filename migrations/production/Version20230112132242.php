<?php

declare(strict_types=1);

namespace App\Migrations\Production;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230112132242 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE git_commit (id INT AUTO_INCREMENT NOT NULL, release_id INT DEFAULT NULL, hash VARCHAR(255) NOT NULL, timestamp DATETIME NOT NULL, message VARCHAR(255) NOT NULL, INDEX IDX_22E0C9BAB12A727D (release_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, email VARCHAR(255) DEFAULT NULL, avatar VARCHAR(255) DEFAULT NULL, role INT NOT NULL, created_timestamp DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE git_commit ADD CONSTRAINT FK_22E0C9BAB12A727D FOREIGN KEY (release_id) REFERENCES github_release (id)');
        $this->addSql('ALTER TABLE github_release ADD commits_handled TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE news_article ADD author_id INT DEFAULT NULL, DROP author');
        $this->addSql('ALTER TABLE news_article ADD CONSTRAINT FK_55DE1280F675F31B FOREIGN KEY (author_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_55DE1280F675F31B ON news_article (author_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE news_article DROP FOREIGN KEY FK_55DE1280F675F31B');
        $this->addSql('ALTER TABLE git_commit DROP FOREIGN KEY FK_22E0C9BAB12A727D');
        $this->addSql('DROP TABLE git_commit');
        $this->addSql('DROP TABLE user');
        $this->addSql('ALTER TABLE github_release DROP commits_handled');
        $this->addSql('DROP INDEX IDX_55DE1280F675F31B ON news_article');
        $this->addSql('ALTER TABLE news_article ADD author VARCHAR(255) NOT NULL, DROP author_id');
    }
}
