<?php

namespace App\Workshop;

use App\Entity\WorkshopItem;
use Doctrine\ORM\EntityManager;

class WorkshopItemRepository {

    public function __construct(
        private EntityManager $em,
    ){}

    public function find(int $id): WorkshopItemObject|null
    {
        $item = $this->em->getRepository(WorkshopItem::class)->find($id);

        if(!$item){
            return null;
        }

        return new WorkshopItemObject($item, $this->em);
    }

}
