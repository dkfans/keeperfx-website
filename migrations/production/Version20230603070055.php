<?php

declare(strict_types=1);

namespace App\Migrations\Production;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230603070055 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE news_article CHANGE short_text excerpt longtext');
        $this->addSql('ALTER TABLE news_article CHANGE text contents longtext');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE news_article CHANGE excerpt short_text longtext');
        $this->addSql('ALTER TABLE news_article CHANGE contents text longtext');
    }
}
