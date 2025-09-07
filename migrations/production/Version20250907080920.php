<?php

declare(strict_types=1);

namespace App\Migrations\Production;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250907080920 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'fix charsets and collations';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE crash_report CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $this->addSql("ALTER TABLE git_commit CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $this->addSql("ALTER TABLE github_alpha_build CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $this->addSql("ALTER TABLE github_release CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $this->addSql("ALTER TABLE launcher_release CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $this->addSql("ALTER TABLE mail CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $this->addSql("ALTER TABLE news_article CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $this->addSql("ALTER TABLE user CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $this->addSql("ALTER TABLE user_cookie_token CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $this->addSql("ALTER TABLE user_oauth_token CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $this->addSql("ALTER TABLE workshop_comment CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $this->addSql("ALTER TABLE workshop_difficulty_rating CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $this->addSql("ALTER TABLE workshop_file CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $this->addSql("ALTER TABLE workshop_image CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $this->addSql("ALTER TABLE workshop_item CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $this->addSql("ALTER TABLE workshop_rating CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $this->addSql("ALTER TABLE workshop_tag CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        $this->addSql('ALTER TABLE crash_report CHANGE description description LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE git_commit CHANGE message message VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE github_alpha_build CHANGE name name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE github_release CHANGE name name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE mail CHANGE body body LONGTEXT DEFAULT NULL, CHANGE html_body html_body LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE news_article CHANGE title title VARCHAR(255) NOT NULL, CHANGE excerpt excerpt LONGTEXT NOT NULL, CHANGE contents contents LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE release_mirror ADD CONSTRAINT FK_954561A7B12A727D FOREIGN KEY (release_id) REFERENCES github_release (id)');
        $this->addSql('ALTER TABLE user CHANGE username username VARCHAR(255) NOT NULL, CHANGE theme theme VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE workshop_comment CHANGE content content LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE workshop_item CHANGE name name VARCHAR(255) NOT NULL, CHANGE description description LONGTEXT DEFAULT NULL, CHANGE install_instructions install_instructions LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE workshop_tag CHANGE name name VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user CHANGE username username VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE theme theme VARCHAR(255) DEFAULT \'default\' NOT NULL');
        $this->addSql('ALTER TABLE crash_report CHANGE description description LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE release_mirror DROP FOREIGN KEY FK_954561A7B12A727D');
        $this->addSql('ALTER TABLE news_article CHANGE title title VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE contents contents LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE excerpt excerpt LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE workshop_comment CHANGE content content LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE github_release CHANGE name name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE github_alpha_build CHANGE name name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE workshop_tag CHANGE name name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE workshop_item CHANGE name name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE description description LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE install_instructions install_instructions LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE git_commit CHANGE message message VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE mail CHANGE body body LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE html_body html_body LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
