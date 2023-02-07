<?php

namespace App\Twig\Extension;

class WorkshopRatingTwigExtension extends \Twig\Extension\AbstractExtension
{
    protected const STAR_FULL  = '<img src="/img/star-full.png" data-rating-score="%s" />';
    protected const STAR_HALF  = '<img src="/img/star-half.png" data-rating-score="%s" />';
    protected const STAR_EMPTY = '<img src="/img/star-empty.png" data-rating-score="%s" />';

    protected const SPAN_STYLE = 'width: 100px; height: 20px; display: inline-block';

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
    public function renderWorkshopRating(int $item_id, float|int|null $rating) : string
    {
        $output = '<span style="' . self::SPAN_STYLE . '" data-workshop-item-id="' . $item_id . '">%s</span>';

        if($rating === null){
            return \sprintf($output, 'N/A');
        }

        $full_stars  = \floor($rating);
        $half_stars  = \floor(($rating - $full_stars) / 0.5);
        $empty_stars = 5 - $full_stars - $half_stars;

        $r = 1;
        $images_string = '';

        for($i = 0; $i < $full_stars; $i++){
            $images_string .= \sprintf(self::STAR_FULL, $r);
            $r++;
        }

        for($i = 0; $i < $half_stars; $i++){
            $images_string .= \sprintf(self::STAR_HALF, $r);
            $r++;
        }

        for($i = 0; $i < $empty_stars; $i++){
            $images_string .= \sprintf(self::STAR_EMPTY, $r);
            $r++;
        }

        return \sprintf($output, $images_string);
    }
}
