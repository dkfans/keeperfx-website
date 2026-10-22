<?php

namespace App;

use App\Config\Config;
use App\Entity\UserAward;
use Doctrine\Common\Collections\Collection;

class AwardHandler
{
    public static function getAwardsFromCollection(Collection $collection): array
    {
        $awards = Config::get('awards.awards');

        $return = [];

        /** @var UserAward $award_entity */
        foreach ($collection as $award_entity) {

            // Get award data for this award
            $award_data = $awards[$award_entity->getAwardId()] ?? [];
            if (empty($award_data)) {
                continue;
            }

            // Get tooltip
            $tooltip = 'Received at: ' . $award_entity->getCreatedTimestamp()->format('Y-m-d');
            if (!empty($award_entity->getInfo())) {
                $tooltip = $award_entity->getInfo() . "\n\n" . $tooltip;
            }

            // Return
            $return[] = \array_merge($award_data, [
                'id'        => $award_entity->getId(),
                'award_id'  => $award_entity->getAwardId(),
                'info'      => $award_entity->getInfo(),
                'tooltip'   => $tooltip,
                'user'      => $award_entity->getUser(),
                'giver'     => $award_entity->getGiver(),
                'timestamp' => $award_entity->getCreatedTimestamp(),
            ]);

        }

        return $return;
    }
}
