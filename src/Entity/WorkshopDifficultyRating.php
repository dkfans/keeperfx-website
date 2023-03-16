<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class WorkshopDifficultyRating extends WorkshopRating {

    #[ORM\ManyToOne(targetEntity: WorkshopItem::class, inversedBy: 'difficulty_ratings')]
    private WorkshopItem $item;
}
