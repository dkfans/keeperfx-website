<?php

declare(strict_types=1);

namespace App\Migrations\Production;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230505180242 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE workshop_image (id INT AUTO_INCREMENT NOT NULL, item_id INT DEFAULT NULL, filename VARCHAR(255) NOT NULL, width INT DEFAULT NULL, height INT DEFAULT NULL, weight INT NOT NULL, created_timestamp DATETIME NOT NULL, INDEX IDX_2DBF5745126F525E (item_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE workshop_image ADD CONSTRAINT FK_2DBF5745126F525E FOREIGN KEY (item_id) REFERENCES workshop_item (id)');

        // Move current thumbnail and screenshots to new WorkshopImage entity
        $items = $this->connection->fetchAllAssociative("SELECT * FROM workshop_item");
        if($items && \is_iterable($items)){
            foreach($items as $item){

                // Define storage dir
                if(!empty($_ENV['APP_WORKSHOP_STORAGE_CLI_PATH'])){
                    $storage_dir = $_ENV['APP_WORKSHOP_STORAGE_CLI_PATH'];
                } elseif (!empty($_ENV['APP_WORKSHOP_STORAGE'])){
                    $storage_dir = $_ENV['APP_WORKSHOP_STORAGE'];
                } else {
                    die('invalid storage dir');
                }

                $storage_dir    .= '/' . $item['id'];
                $screenshot_dir  = $storage_dir . '/screenshots';
                $images_dir      = $storage_dir . '/images';

                if(\is_dir($storage_dir)){

                    $weight = 0;

                    // Create new images dir
                    if(!\is_dir($images_dir)){
                        \mkdir($images_dir);
                        if(!\is_dir($images_dir)){
                            throw new \Exception("failed to create 'images' dir: {$images_dir}");
                        }
                    }

                    // Handle thumbnail
                    if($item['thumbnail'] !== null){
                        $thumbnail_path     = $storage_dir . '/' . $item['thumbnail'];
                        $thumbnail_new_path = $images_dir . '/' . $item['thumbnail'];
                        if(\file_exists($thumbnail_path)){

                            \rename($thumbnail_path, $thumbnail_new_path);

                            $width  = 'NULL';
                            $height = 'NULL';
                            $size   = @\getimagesize($thumbnail_new_path);
                            if($size && \is_array($size)){
                                $width  = $size[0];
                                $height = $size[1];
                            }

                            $this->addSql(
                                "INSERT INTO workshop_image (item_id, filename, width, height, weight, created_timestamp) VALUES ({$item['id']}, '{$item['thumbnail']}', {$width}, {$height}, {$weight}, '{$item['created_timestamp']}')"
                            );

                            $weight++;
                        }
                    }

                    // Handle screenshots
                    if(\is_dir($screenshot_dir)){
                        foreach(\glob($screenshot_dir . '/*.*') as $screenshot_filepath){
                            $screenshot_filename = basename($screenshot_filepath);
                            $screenshot_path     = $screenshot_dir . '/' . $screenshot_filename;
                            $screenshot_new_path = $images_dir . '/' . $screenshot_filename;
                            if(\file_exists($screenshot_path)){

                                \rename($screenshot_path, $screenshot_new_path);

                                $width  = 'NULL';
                                $height = 'NULL';
                                $size   = @\getimagesize($screenshot_new_path);
                                if($size && \is_array($size)){
                                    $width  = $size[0];
                                    $height = $size[1];
                                }

                                $this->addSql(
                                    "INSERT INTO workshop_image (item_id, filename, width, height, weight, created_timestamp) VALUES ({$item['id']}, '{$screenshot_filename}', {$width}, {$height}, {$weight}, '{$item['created_timestamp']}')"
                                );

                                $weight++;
                            }
                        }

                        // Remove leftover screenshot dir
                        @rmdir($screenshot_dir);
                    }
                }
            }
        }

        $this->addSql('ALTER TABLE workshop_item DROP thumbnail');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workshop_image DROP FOREIGN KEY FK_2DBF5745126F525E');
        $this->addSql('DROP TABLE workshop_image');

        $this->addSql('ALTER TABLE workshop_item ADD thumbnail VARCHAR(255) DEFAULT NULL');
    }
}
