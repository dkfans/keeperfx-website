<?php

declare(strict_types=1);

namespace App\Migrations\Production;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260202181724 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'big cascading change';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE git_commit DROP FOREIGN KEY FK_22E0C9BAB12A727D');
        $this->addSql('ALTER TABLE git_commit CHANGE release_id release_id INT NOT NULL');
        $this->addSql('ALTER TABLE git_commit ADD CONSTRAINT FK_22E0C9BAB12A727D FOREIGN KEY (release_id) REFERENCES github_release (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE news_article DROP FOREIGN KEY FK_55DE1280F675F31B');
        $this->addSql('ALTER TABLE news_article CHANGE author_id author_id INT NOT NULL');
        $this->addSql('ALTER TABLE news_article ADD CONSTRAINT FK_55DE1280F675F31B FOREIGN KEY (author_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE release_mirror DROP FOREIGN KEY FK_954561A7B12A727D');
        $this->addSql('ALTER TABLE release_mirror CHANGE release_id release_id INT NOT NULL');
        $this->addSql('ALTER TABLE release_mirror ADD CONSTRAINT FK_954561A7B12A727D FOREIGN KEY (release_id) REFERENCES github_release (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_bio DROP FOREIGN KEY FK_36360BF0A76ED395');
        $this->addSql('ALTER TABLE user_bio CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE user_bio ADD CONSTRAINT FK_36360BF0A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_cookie_token DROP FOREIGN KEY FK_EBC41239A76ED395');
        $this->addSql('ALTER TABLE user_cookie_token CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE user_cookie_token ADD CONSTRAINT FK_EBC41239A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_email_verification DROP FOREIGN KEY FK_A3A6C5A3A76ED395');
        $this->addSql('ALTER TABLE user_email_verification CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE user_email_verification ADD CONSTRAINT FK_A3A6C5A3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_ip_log DROP FOREIGN KEY FK_E1A9AAE0A76ED395');
        $this->addSql('ALTER TABLE user_ip_log CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE user_ip_log ADD CONSTRAINT FK_E1A9AAE0A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_notification DROP FOREIGN KEY FK_3F980AC8A76ED395');
        $this->addSql('ALTER TABLE user_notification CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE user_notification ADD CONSTRAINT FK_3F980AC8A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_notification_setting DROP FOREIGN KEY FK_344BE150A76ED395');
        $this->addSql('ALTER TABLE user_notification_setting CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE user_notification_setting ADD CONSTRAINT FK_344BE150A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_oauth_token DROP FOREIGN KEY FK_712F82BFA76ED395');
        $this->addSql('ALTER TABLE user_oauth_token CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE user_oauth_token ADD CONSTRAINT FK_712F82BFA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_password_reset_token DROP FOREIGN KEY FK_D55A77C9A76ED395');
        $this->addSql('ALTER TABLE user_password_reset_token CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE user_password_reset_token ADD CONSTRAINT FK_D55A77C9A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE workshop_comment DROP FOREIGN KEY FK_D63B407B727ACA70');
        $this->addSql('ALTER TABLE workshop_comment DROP FOREIGN KEY FK_D63B407BA76ED395');
        $this->addSql('ALTER TABLE workshop_comment CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE workshop_comment ADD CONSTRAINT FK_D63B407B727ACA70 FOREIGN KEY (parent_id) REFERENCES workshop_comment (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE workshop_comment ADD CONSTRAINT FK_D63B407BA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE workshop_comment_report DROP FOREIGN KEY FK_9E57168A76ED395');
        $this->addSql('ALTER TABLE workshop_comment_report DROP FOREIGN KEY FK_9E57168F8697D13');
        $this->addSql('ALTER TABLE workshop_comment_report CHANGE user_id user_id INT NOT NULL, CHANGE comment_id comment_id INT NOT NULL');
        $this->addSql('ALTER TABLE workshop_comment_report ADD CONSTRAINT FK_9E57168A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE workshop_comment_report ADD CONSTRAINT FK_9E57168F8697D13 FOREIGN KEY (comment_id) REFERENCES workshop_comment (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE workshop_difficulty_rating DROP FOREIGN KEY FK_6D0C6160A76ED395');
        $this->addSql('ALTER TABLE workshop_difficulty_rating DROP FOREIGN KEY FK_6D0C6160126F525E');
        $this->addSql('ALTER TABLE workshop_difficulty_rating CHANGE item_id item_id INT NOT NULL, CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE workshop_difficulty_rating ADD CONSTRAINT FK_6D0C6160A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE workshop_difficulty_rating ADD CONSTRAINT FK_6D0C6160126F525E FOREIGN KEY (item_id) REFERENCES workshop_item (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE workshop_file DROP FOREIGN KEY FK_B6181F57126F525E');
        $this->addSql('ALTER TABLE workshop_file CHANGE item_id item_id INT NOT NULL');
        $this->addSql('ALTER TABLE workshop_file ADD CONSTRAINT FK_B6181F57126F525E FOREIGN KEY (item_id) REFERENCES workshop_item (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE workshop_image DROP FOREIGN KEY FK_2DBF5745126F525E');
        $this->addSql('ALTER TABLE workshop_image CHANGE item_id item_id INT NOT NULL');
        $this->addSql('ALTER TABLE workshop_image ADD CONSTRAINT FK_2DBF5745126F525E FOREIGN KEY (item_id) REFERENCES workshop_item (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE workshop_item DROP FOREIGN KEY FK_259C0C59919E5513');
        $this->addSql('ALTER TABLE workshop_item ADD CONSTRAINT FK_259C0C59919E5513 FOREIGN KEY (submitter_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE workshop_rating DROP FOREIGN KEY FK_25035D0B126F525E');
        $this->addSql('ALTER TABLE workshop_rating DROP FOREIGN KEY FK_25035D0BA76ED395');
        $this->addSql('ALTER TABLE workshop_rating CHANGE item_id item_id INT NOT NULL, CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE workshop_rating ADD CONSTRAINT FK_25035D0B126F525E FOREIGN KEY (item_id) REFERENCES workshop_item (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE workshop_rating ADD CONSTRAINT FK_25035D0BA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_cookie_token DROP FOREIGN KEY FK_EBC41239A76ED395');
        $this->addSql('ALTER TABLE user_cookie_token CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user_cookie_token ADD CONSTRAINT FK_EBC41239A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_notification DROP FOREIGN KEY FK_3F980AC8A76ED395');
        $this->addSql('ALTER TABLE user_notification CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user_notification ADD CONSTRAINT FK_3F980AC8A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE workshop_image DROP FOREIGN KEY FK_2DBF5745126F525E');
        $this->addSql('ALTER TABLE workshop_image CHANGE item_id item_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE workshop_image ADD CONSTRAINT FK_2DBF5745126F525E FOREIGN KEY (item_id) REFERENCES workshop_item (id)');
        $this->addSql('ALTER TABLE user_ip_log DROP FOREIGN KEY FK_E1A9AAE0A76ED395');
        $this->addSql('ALTER TABLE user_ip_log CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user_ip_log ADD CONSTRAINT FK_E1A9AAE0A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE workshop_comment_report DROP FOREIGN KEY FK_9E57168A76ED395');
        $this->addSql('ALTER TABLE workshop_comment_report DROP FOREIGN KEY FK_9E57168F8697D13');
        $this->addSql('ALTER TABLE workshop_comment_report CHANGE user_id user_id INT DEFAULT NULL, CHANGE comment_id comment_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE workshop_comment_report ADD CONSTRAINT FK_9E57168A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE workshop_comment_report ADD CONSTRAINT FK_9E57168F8697D13 FOREIGN KEY (comment_id) REFERENCES workshop_comment (id)');
        $this->addSql('ALTER TABLE release_mirror DROP FOREIGN KEY FK_954561A7B12A727D');
        $this->addSql('ALTER TABLE release_mirror CHANGE release_id release_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE release_mirror ADD CONSTRAINT FK_954561A7B12A727D FOREIGN KEY (release_id) REFERENCES github_release (id)');
        $this->addSql('ALTER TABLE user_oauth_token DROP FOREIGN KEY FK_712F82BFA76ED395');
        $this->addSql('ALTER TABLE user_oauth_token CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user_oauth_token ADD CONSTRAINT FK_712F82BFA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE news_article DROP FOREIGN KEY FK_55DE1280F675F31B');
        $this->addSql('ALTER TABLE news_article CHANGE author_id author_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE news_article ADD CONSTRAINT FK_55DE1280F675F31B FOREIGN KEY (author_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE workshop_comment DROP FOREIGN KEY FK_D63B407BA76ED395');
        $this->addSql('ALTER TABLE workshop_comment DROP FOREIGN KEY FK_D63B407B727ACA70');
        $this->addSql('ALTER TABLE workshop_comment CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE workshop_comment ADD CONSTRAINT FK_D63B407BA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE workshop_comment ADD CONSTRAINT FK_D63B407B727ACA70 FOREIGN KEY (parent_id) REFERENCES workshop_comment (id)');
        $this->addSql('ALTER TABLE user_bio DROP FOREIGN KEY FK_36360BF0A76ED395');
        $this->addSql('ALTER TABLE user_bio CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user_bio ADD CONSTRAINT FK_36360BF0A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE workshop_difficulty_rating DROP FOREIGN KEY FK_6D0C6160126F525E');
        $this->addSql('ALTER TABLE workshop_difficulty_rating DROP FOREIGN KEY FK_6D0C6160A76ED395');
        $this->addSql('ALTER TABLE workshop_difficulty_rating CHANGE item_id item_id INT DEFAULT NULL, CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE workshop_difficulty_rating ADD CONSTRAINT FK_6D0C6160126F525E FOREIGN KEY (item_id) REFERENCES workshop_item (id)');
        $this->addSql('ALTER TABLE workshop_difficulty_rating ADD CONSTRAINT FK_6D0C6160A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE workshop_rating DROP FOREIGN KEY FK_25035D0B126F525E');
        $this->addSql('ALTER TABLE workshop_rating DROP FOREIGN KEY FK_25035D0BA76ED395');
        $this->addSql('ALTER TABLE workshop_rating CHANGE item_id item_id INT DEFAULT NULL, CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE workshop_rating ADD CONSTRAINT FK_25035D0B126F525E FOREIGN KEY (item_id) REFERENCES workshop_item (id)');
        $this->addSql('ALTER TABLE workshop_rating ADD CONSTRAINT FK_25035D0BA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_password_reset_token DROP FOREIGN KEY FK_D55A77C9A76ED395');
        $this->addSql('ALTER TABLE user_password_reset_token CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user_password_reset_token ADD CONSTRAINT FK_D55A77C9A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_email_verification DROP FOREIGN KEY FK_A3A6C5A3A76ED395');
        $this->addSql('ALTER TABLE user_email_verification CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user_email_verification ADD CONSTRAINT FK_A3A6C5A3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE workshop_file DROP FOREIGN KEY FK_B6181F57126F525E');
        $this->addSql('ALTER TABLE workshop_file CHANGE item_id item_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE workshop_file ADD CONSTRAINT FK_B6181F57126F525E FOREIGN KEY (item_id) REFERENCES workshop_item (id)');
        $this->addSql('ALTER TABLE user_notification_setting DROP FOREIGN KEY FK_344BE150A76ED395');
        $this->addSql('ALTER TABLE user_notification_setting CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user_notification_setting ADD CONSTRAINT FK_344BE150A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE workshop_item DROP FOREIGN KEY FK_259C0C59919E5513');
        $this->addSql('ALTER TABLE workshop_item ADD CONSTRAINT FK_259C0C59919E5513 FOREIGN KEY (submitter_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE git_commit DROP FOREIGN KEY FK_22E0C9BAB12A727D');
        $this->addSql('ALTER TABLE git_commit CHANGE release_id release_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE git_commit ADD CONSTRAINT FK_22E0C9BAB12A727D FOREIGN KEY (release_id) REFERENCES github_release (id)');
    }
}
