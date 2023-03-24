<?php

namespace App\Workshop;

use App\Entity\WorkshopItem;
use Doctrine\ORM\EntityManager;

class WorkshopItemFactory {

    public function __construct(
        private EntityManager $em,
    ){}

    public function create(): WorkshopItemInstance
    {
        return new WorkshopItemInstance(new WorkshopItem(), $this->em);
    }
}
