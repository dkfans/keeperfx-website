<?php

declare(strict_types=1);

namespace App\Migrations\Production;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230331144830 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_oauth_token (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, type VARCHAR(255) NOT NULL, token VARCHAR(255) NOT NULL, uid VARCHAR(255) NOT NULL, refresh_token VARCHAR(255) NOT NULL, created_timestamp DATETIME NOT NULL, expires_timestamp DATETIME NOT NULL, INDEX IDX_712F82BFA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_oauth_token ADD CONSTRAINT FK_712F82BFA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user CHANGE password password VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user_cookie_token ADD oauth_token_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user_cookie_token ADD CONSTRAINT FK_EBC4123961A264A5 FOREIGN KEY (oauth_token_id) REFERENCES user_oauth_token (id)');
        $this->addSql('CREATE INDEX IDX_EBC4123961A264A5 ON user_cookie_token (oauth_token_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_cookie_token DROP FOREIGN KEY FK_EBC4123961A264A5');
        $this->addSql('ALTER TABLE user_oauth_token DROP FOREIGN KEY FK_712F82BFA76ED395');
        $this->addSql('DROP TABLE user_oauth_token');
        $this->addSql('DROP INDEX IDX_EBC4123961A264A5 ON user_cookie_token');
        $this->addSql('ALTER TABLE user_cookie_token DROP oauth_token_id');
        $this->addSql('ALTER TABLE user CHANGE password password VARCHAR(255) NOT NULL');
    }
}
