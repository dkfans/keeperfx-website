<?php

namespace App\Twig\Extension;

use App\AwardHandler;
use Doctrine\Common\Collections\Collection;

class AwardTwigExtension extends \Twig\Extension\AbstractExtension
{
    public function getName(): string
    {
        return 'award_extension';
    }

    /**
     * @return \Twig\TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new \Twig\TwigFunction(
                'get_award_data',
                [$this, 'getAwardData'],
            ),
        ];
    }

    /**
     * Retrieve an asset URI.
     */
    public function getAwardData(Collection $awards): array
    {
        return AwardHandler::getAwardsFromCollection($awards);
    }
}
