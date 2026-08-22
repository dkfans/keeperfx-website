<?php

namespace App\Doctrine;

use Doctrine\ORM\Event\PreFlushEventArgs;

class ReadOnlyListener
{
    /**
     * Clears the entity manager before it calculates changes.
     * This detaches all entities, meaning no database writes will occur.
     */
    public function preFlush(PreFlushEventArgs $args): void
    {
        $entityManager = $args->getObjectManager();
        $entityManager->clear();
    }
}
