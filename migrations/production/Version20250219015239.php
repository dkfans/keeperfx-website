<?php

declare(strict_types=1);

namespace App\Migrations\Production;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250219015239 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'release versions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE github_release ADD version VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE github_alpha_build ADD version VARCHAR(255) DEFAULT NULL');

        // Fix possible existing stable versions
        $items = $this->connection->fetchAllAssociative("SELECT * FROM github_release");
        if($items && \is_iterable($items)){
            foreach($items as $item){
                if(\preg_match('/^KeeperFX (\d+\.\d+\.\d+)$/', $item['name'], $matches)){
                    $this->addSql('UPDATE github_release SET version = \'' . $matches[1] . '\' WHERE id = ' . $item['id']);
                }
            }
        }

        // Fix possible existing alpha versions
        $items = $this->connection->fetchAllAssociative("SELECT * FROM github_alpha_build");
        if($items && \is_iterable($items)){
            foreach($items as $item){
                if(\preg_match('/^keeperfx\-(\d+\_\d+\_\d+\_\d+)\_Alpha\-patch$/', $item['name'], $matches)){
                    $this->addSql('UPDATE github_alpha_build SET version = \'' . \str_replace('_', '.', $matches[1]) . '\' WHERE id = ' . $item['id']);
                }
            }
        }

        // Hardcoded release updates
        // because we don't need regex for these
        $this->addSql('UPDATE github_release SET version = \'0.4.8.2154\' WHERE name = \'KeeperFX 0.4.8 Build 2154\'');
        $this->addSql('UPDATE github_release SET version = \'0.4.9.2762\' WHERE name = \'KeeperFX 0.4.9 Build 2762\'');
        $this->addSql('UPDATE github_release SET version = \'0.5.0.3080\' WHERE name = \'KeeperFX 0.5.0 Build 3080\'');
        $this->addSql('UPDATE github_release SET version = \'0.5.0.3081\' WHERE name = \'KeeperFX 0.5.0b Build 3081\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE github_release DROP version');
        $this->addSql('ALTER TABLE github_alpha_build DROP version');
   }
}
