<?php

declare(strict_types=1);

namespace App\Migrations\Production;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230531181320 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workshop_item ADD creation_orderby_timestamp DATETIME NOT NULL');

        $items = $this->connection->fetchAllAssociative("SELECT * FROM workshop_item");
        if($items && \is_iterable($items)){
            foreach($items as $item){

                $orderby_timestamp = $item['created_timestamp'];

                if(isset($item['original_creation_date'])){
                    $orderby_timestamp = $item['original_creation_date'];
                }

                $this->addSql("UPDATE workshop_item SET `creation_orderby_timestamp` = '{$orderby_timestamp}' WHERE `id` = {$item['id']}");
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workshop_item DROP creation_orderby_timestamp');
    }
}
