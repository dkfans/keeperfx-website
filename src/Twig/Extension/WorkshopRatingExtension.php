<?php

/*
<img src="/img/star-full.png" />

<img src="/img/star-no.png" />
*/

namespace App\Twig\Extension;

class WorkshopRatingExtension extends \Twig\Extension\AbstractExtension
{
    protected const STAR_FULL = '<img src="/img/star-full.png" />';
    protected const STAR_HALF = '<img src="/img/star-half.png" />';
    protected const STAR_NONE = '<img src="/img/star-none.png" />';

    public function getName(): string
    {
        return 'workshop_rating_extension';
    }

    public function getFunctions(): array
    {
        return [
            new \Twig\TwigFunction(
                'workshop_rating',
                [$this, 'renderWorkshopRating'],
                ['is_safe' => ['html']]
            ),
        ];
    }

    /**
     * Retrieve workshop horny face rating
     *
     * @param float|int $rating
     * @return string
     */
    public function renderWorkshopRating(float|int $rating): string
    {
        if($rating < 0.25){
            return self::STAR_NONE . self::STAR_NONE . self::STAR_NONE . self::STAR_NONE . self::STAR_NONE;
        }
        if($rating < 0.75){
            return self::STAR_HALF . self::STAR_NONE . self::STAR_NONE . self::STAR_NONE . self::STAR_NONE;
        }
        if($rating < 1.25){
            return self::STAR_FULL . self::STAR_NONE . self::STAR_NONE . self::STAR_NONE . self::STAR_NONE;
        }
        if($rating < 1.75){
            return self::STAR_FULL . self::STAR_HALF . self::STAR_NONE . self::STAR_NONE . self::STAR_NONE;
        }
        if($rating < 2.25){
            return self::STAR_FULL . self::STAR_FULL . self::STAR_NONE . self::STAR_NONE . self::STAR_NONE;
        }
        if($rating < 2.75){
            return self::STAR_FULL . self::STAR_FULL . self::STAR_HALF . self::STAR_NONE . self::STAR_NONE;
        }
        if($rating < 3.25){
            return self::STAR_FULL . self::STAR_FULL . self::STAR_FULL . self::STAR_NONE . self::STAR_NONE;
        }
        if($rating < 3.75){
            return self::STAR_FULL . self::STAR_FULL . self::STAR_FULL . self::STAR_HALF . self::STAR_NONE;
        }
        if($rating < 4.25){
            return self::STAR_FULL . self::STAR_FULL . self::STAR_FULL . self::STAR_FULL . self::STAR_NONE;
        }
        if($rating < 4.75){
            return self::STAR_FULL . self::STAR_FULL . self::STAR_FULL . self::STAR_FULL . self::STAR_HALF;
        }
        return self::STAR_FULL . self::STAR_FULL . self::STAR_FULL . self::STAR_FULL . self::STAR_FULL;
    }
}
