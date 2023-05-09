<?php

declare(strict_types=1);

namespace App\Migrations\Production;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230403223250 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE workshop_file (id INT AUTO_INCREMENT NOT NULL, item_id INT DEFAULT NULL, filename VARCHAR(255) NOT NULL, storage_filename VARCHAR(255) NOT NULL, size INT NOT NULL, download_count INT NOT NULL, version VARCHAR(255) DEFAULT NULL, scan_status INT NOT NULL, created_timestamp DATETIME NOT NULL, INDEX IDX_B6181F57126F525E (item_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE workshop_file ADD CONSTRAINT FK_B6181F57126F525E FOREIGN KEY (item_id) REFERENCES workshop_item (id)');

        // Move current files to new WorkshopFile entity
        $items = $this->connection->fetchAllAssociative("SELECT * FROM workshop_item");
        if($items && \is_iterable($items)){
            foreach($items as $item){

                // Define directories
                $storage_dir = $_ENV['APP_WORKSHOP_STORAGE'] . '/' . $item['id'];
                $files_dir   = $storage_dir . '/files';

                // Check if workshop dir exists
                if(!\is_dir($storage_dir)){
                    continue;
                }

                // Create new files dir
                if(!\is_dir($files_dir)){
                    \mkdir($files_dir);
                    if(!\is_dir($files_dir)){
                        throw new \Exception("failed to create 'files' dir: {$files_dir}");
                    }
                }

                // Get file
                $file_path = $storage_dir . '/' . $item['filename'];
                if(!\file_exists($file_path)){
                    continue;
                }

                // Move file
                $new_file_path = $files_dir . '/' . $item['filename'];
                \rename($file_path, $new_file_path);

                // Get filesize
                $filesize = @\filesize($new_file_path) ?? 0;

                // Insert into DB
                $this->addSql(
                    "INSERT INTO workshop_file (item_id, filename, storage_filename, size, download_count, scan_status, created_timestamp) VALUES ({$item['id']}, '{$item['filename']}', '{$item['filename']}', {$filesize}, {$item['download_count']}, 2, '{$item['created_timestamp']}')"
                );
            }
        }

        $this->addSql('ALTER TABLE workshop_item DROP filename, CHANGE description description LONGTEXT DEFAULT NULL, CHANGE install_instructions install_instructions LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE workshop_file DROP FOREIGN KEY FK_B6181F57126F525E');
        $this->addSql('DROP TABLE workshop_file');
        $this->addSql('ALTER TABLE workshop_item ADD filename VARCHAR(255) DEFAULT NULL, CHANGE description description LONGTEXT NOT NULL, CHANGE install_instructions install_instructions LONGTEXT NOT NULL');
    }
}
