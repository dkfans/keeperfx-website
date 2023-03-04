<?php

namespace App\Twig\Extension;

class WorkshopRatingTwigExtension extends \Twig\Extension\AbstractExtension
{

    protected const STAR_FULL               = '/img/rating/star-full.png';
    protected const STAR_HALF               = '/img/rating/star-half.png';
    protected const STAR_EMPTY              = '/img/rating/star-empty.png';
    protected const STAR_UNRATED            = '/img/rating/star-unrated.png';

    protected const STAR_DIFFICULTY_FULL    = '/img/rating/star-difficulty-full.png';
    protected const STAR_DIFFICULTY_HALF    = '/img/rating/star-difficulty-half.png';
    protected const STAR_DIFFICULTY_EMPTY   = '/img/rating/star-difficulty-empty.png';
    protected const STAR_DIFFICULTY_UNRATED = '/img/rating/star-difficulty-unrated.png';

    protected const IMG_STYLE  = 'width: 20px; height: 20px';
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

    private static function createImageElementString(string $image_uri): string
    {
        return '<img src="' . $image_uri . '" style="' . self::IMG_STYLE . '" data-rating-score="%s" />';
    }

    /**
     * Retrieve workshop item rating 'stars'
     *
     * @param int $item_id
     * @param float|int|null $rating
     * @return string
     */
    public function renderWorkshopRating(int $item_id, float|int|null $rating) : string
    {
        $output = '<span style="' . self::SPAN_STYLE . '" data-workshop-item-id="' . $item_id . '">%s</span>';

        if($rating === null){
            return \sprintf($output, \sprintf(
                \str_repeat(self::createImageElementString(self::STAR_UNRATED), 5),
                1, 2, 3, 4, 5
            ));
        }

        $full_stars  = \floor($rating);
        $half_stars  = \floor(($rating - $full_stars) / 0.5);
        $empty_stars = 5 - $full_stars - $half_stars;

        $r = 1;
        $images_string = '';

        for($i = 0; $i < $full_stars; $i++){
            $images_string .= \sprintf(self::createImageElementString(self::STAR_FULL), $r);
            $r++;
        }

        for($i = 0; $i < $half_stars; $i++){
            $images_string .= \sprintf(self::createImageElementString(self::STAR_HALF), $r);
            $r++;
        }

        for($i = 0; $i < $empty_stars; $i++){
            $images_string .= \sprintf(self::createImageElementString(self::STAR_EMPTY), $r);
            $r++;
        }

        return \sprintf($output, $images_string);
    }
}
