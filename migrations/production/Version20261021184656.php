<?php

declare(strict_types=1);

namespace App\Migrations\Production;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261021184656 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add user awards';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_award (id INT AUTO_INCREMENT NOT NULL, award_id VARCHAR(255) NOT NULL, info LONGTEXT DEFAULT NULL, weight INT NOT NULL, created_timestamp DATETIME NOT NULL, user_id INT NOT NULL, giver_id INT DEFAULT NULL, INDEX IDX_6899D5BFA76ED395 (user_id), INDEX IDX_6899D5BF75BD1D29 (giver_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE user_award ADD CONSTRAINT FK_6899D5BFA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_award ADD CONSTRAINT FK_6899D5BF75BD1D29 FOREIGN KEY (giver_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_award DROP FOREIGN KEY FK_6899D5BFA76ED395');
        $this->addSql('ALTER TABLE user_award DROP FOREIGN KEY FK_6899D5BF75BD1D29');
        $this->addSql('DROP TABLE user_award');
    }
}
