<?php

namespace App\Twig\Extension;

class WorkshopRatingTwigExtension extends \Twig\Extension\AbstractExtension
{

    protected const STAR_FULL               = '/img/rating/star2-full.png';
    protected const STAR_HALF               = '/img/rating/star2-half.png';
    protected const STAR_EMPTY              = '/img/rating/star2-empty.png';
    protected const STAR_UNRATED            = '/img/rating/star2-unrated.png';

    protected const STAR_DIFFICULTY_FULL    = '/img/rating/star-difficulty-full.png';
    protected const STAR_DIFFICULTY_HALF    = '/img/rating/star-difficulty-half.png';
    protected const STAR_DIFFICULTY_EMPTY   = '/img/rating/star-difficulty-empty.png';
    protected const STAR_DIFFICULTY_UNRATED = '/img/rating/star-difficulty-unrated.png';

    protected const IMG_STYLE  = 'width: 18px; height: 18px;';
    protected const SPAN_STYLE = 'width: 90px; height: 18px; display: inline-block';

    public function getName(): string
    {
        return 'workshop_rating_extension';
    }

    public function getFunctions(): array
    {
        return [
            new \Twig\TwigFunction(
                'render_workshop_overall_rating',
                [$this, 'renderWorkshopOverallRating'],
                ['is_safe' => ['html']]
            ),
            new \Twig\TwigFunction(
                'render_workshop_difficulty_rating',
                [$this, 'renderWorkshopDifficultyRating'],
                ['is_safe' => ['html']]
            ),
        ];
    }

    /**
     * Create an image element with the correct attributes.
     *
     * @param string $image_uri
     * @return string
     */
    private static function createImageElementString(string $image_uri): string
    {
        return '<img src="' . $image_uri . '" style="' . self::IMG_STYLE . '" data-rating-score="%s" />';
    }

    /**
     * Retrieve a span with the overall rating for a workshop item.
     *
     * @param int $item_id
     * @param float|int|null $rating
     * @return string
     */
    public function renderWorkshopOverallRating(int $item_id, float|int|null $rating) : string
    {
        return self::createStarContainerSpan($item_id, $rating, $type = 'overall', [
            'full'    => self::STAR_FULL,
            'half'    => self::STAR_HALF,
            'empty'   => self::STAR_EMPTY,
            'unrated' => self::STAR_UNRATED
        ]);
    }

    /**
     * Retrieve a span with the difficulty rating for a workshop item.
     *
     * @param int $item_id
     * @param float|int|null $rating
     * @return string
     */
    public function renderWorkshopDifficultyRating(int $item_id, float|int|null $rating) : string
    {
        return self::createStarContainerSpan($item_id, $rating, $type = 'difficulty', [
            'full'    => self::STAR_DIFFICULTY_FULL,
            'half'    => self::STAR_DIFFICULTY_HALF,
            'empty'   => self::STAR_DIFFICULTY_EMPTY,
            'unrated' => self::STAR_DIFFICULTY_UNRATED
        ]);
    }

    /**
     * Render a workshop rating span.
     *
     * @param integer $item_id
     * @param float|integer|null $rating
     * @param array $stars
     * @param string $type    Type
     * @return string
     */
    private static function createStarContainerSpan(int $item_id, float|int|null $rating, string $type = 'overall', array $stars = [])
    {
        $output = '<span ' .
            'style="' . self::SPAN_STYLE . '" ' .
            'data-workshop-item-id="' . $item_id . '" ' .
            'data-workshop-rating-type="' . $type . '" ' .
            'data-workshop-rating-score="' . \round($rating, 2) . '" ' .
            '>%s</span>';

        if($rating === null){
            return \sprintf($output, \sprintf(
                \str_repeat(self::createImageElementString($stars['unrated']), 5),
                1, 2, 3, 4, 5
            ));
        }

        $full_stars  = \floor($rating);
        $half_stars  = \floor(($rating - $full_stars) / 0.5);
        $empty_stars = 5 - $full_stars - $half_stars;

        $r = 1;
        $images_string = '';

        for($i = 0; $i < $full_stars; $i++){
            $images_string .= \sprintf(self::createImageElementString($stars['full']), $r);
            $r++;
        }

        for($i = 0; $i < $half_stars; $i++){
            $images_string .= \sprintf(self::createImageElementString($stars['half']), $r);
            $r++;
        }

        for($i = 0; $i < $empty_stars; $i++){
            $images_string .= \sprintf(self::createImageElementString($stars['empty']), $r);
            $r++;
        }

        return \sprintf($output, $images_string);
    }
}
