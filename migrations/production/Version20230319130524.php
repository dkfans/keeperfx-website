<?php

declare(strict_types=1);

namespace App\Migrations\Production;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230319130524 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE workshop_comment (id INT AUTO_INCREMENT NOT NULL, item_id INT DEFAULT NULL, user_id INT DEFAULT NULL, content LONGTEXT NOT NULL, created_timestamp DATETIME NOT NULL, updated_timestamp DATETIME NOT NULL, INDEX IDX_D63B407B126F525E (item_id), INDEX IDX_D63B407BA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE workshop_difficulty_rating (id INT AUTO_INCREMENT NOT NULL, item_id INT DEFAULT NULL, user_id INT DEFAULT NULL, score INT NOT NULL, created_timestamp DATETIME NOT NULL, updated_timestamp DATETIME NOT NULL, INDEX IDX_6D0C6160126F525E (item_id), INDEX IDX_6D0C6160A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE workshop_item (id INT AUTO_INCREMENT NOT NULL, submitter_id INT DEFAULT NULL, min_game_build_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, map_number INT DEFAULT NULL, type INT NOT NULL, created_timestamp DATETIME NOT NULL, updated_timestamp DATETIME NOT NULL, description LONGTEXT NOT NULL, install_instructions LONGTEXT NOT NULL, filename VARCHAR(255) DEFAULT NULL, thumbnail VARCHAR(255) DEFAULT NULL, is_accepted TINYINT(1) NOT NULL, download_count INT NOT NULL, original_author VARCHAR(255) DEFAULT NULL, original_creation_date DATETIME DEFAULT NULL, rating_score NUMERIC(3, 2) DEFAULT NULL, difficulty_rating_score NUMERIC(3, 2) DEFAULT NULL, INDEX IDX_259C0C59919E5513 (submitter_id), INDEX IDX_259C0C5921AD222A (min_game_build_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE workshop_rating (id INT AUTO_INCREMENT NOT NULL, item_id INT DEFAULT NULL, user_id INT DEFAULT NULL, score INT NOT NULL, created_timestamp DATETIME NOT NULL, updated_timestamp DATETIME NOT NULL, INDEX IDX_25035D0B126F525E (item_id), INDEX IDX_25035D0BA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE workshop_tag (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE workshop_comment ADD CONSTRAINT FK_D63B407B126F525E FOREIGN KEY (item_id) REFERENCES workshop_item (id)');
        $this->addSql('ALTER TABLE workshop_comment ADD CONSTRAINT FK_D63B407BA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE workshop_difficulty_rating ADD CONSTRAINT FK_6D0C6160126F525E FOREIGN KEY (item_id) REFERENCES workshop_item (id)');
        $this->addSql('ALTER TABLE workshop_difficulty_rating ADD CONSTRAINT FK_6D0C6160A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE workshop_item ADD CONSTRAINT FK_259C0C59919E5513 FOREIGN KEY (submitter_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE workshop_item ADD CONSTRAINT FK_259C0C5921AD222A FOREIGN KEY (min_game_build_id) REFERENCES github_release (id)');
        $this->addSql('ALTER TABLE workshop_rating ADD CONSTRAINT FK_25035D0B126F525E FOREIGN KEY (item_id) REFERENCES workshop_item (id)');
        $this->addSql('ALTER TABLE workshop_rating ADD CONSTRAINT FK_25035D0BA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE workshop_comment DROP FOREIGN KEY FK_D63B407B126F525E');
        $this->addSql('ALTER TABLE workshop_comment DROP FOREIGN KEY FK_D63B407BA76ED395');
        $this->addSql('ALTER TABLE workshop_difficulty_rating DROP FOREIGN KEY FK_6D0C6160126F525E');
        $this->addSql('ALTER TABLE workshop_difficulty_rating DROP FOREIGN KEY FK_6D0C6160A76ED395');
        $this->addSql('ALTER TABLE workshop_item DROP FOREIGN KEY FK_259C0C59919E5513');
        $this->addSql('ALTER TABLE workshop_item DROP FOREIGN KEY FK_259C0C5921AD222A');
        $this->addSql('ALTER TABLE workshop_rating DROP FOREIGN KEY FK_25035D0B126F525E');
        $this->addSql('ALTER TABLE workshop_rating DROP FOREIGN KEY FK_25035D0BA76ED395');
        $this->addSql('DROP TABLE workshop_comment');
        $this->addSql('DROP TABLE workshop_difficulty_rating');
        $this->addSql('DROP TABLE workshop_item');
        $this->addSql('DROP TABLE workshop_rating');
        $this->addSql('DROP TABLE workshop_tag');
    }
}
