<?php

namespace App\Workshop;

use App\Entity\WorkshopItem;
use Doctrine\ORM\EntityManager;

class WorkshopItemFactory {

    public function __construct(
        private EntityManager $em,
    ){}

    public function create(): WorkshopItemObject
    {
        return new WorkshopItemObject(new WorkshopItem(), $this->em);
    }
}
