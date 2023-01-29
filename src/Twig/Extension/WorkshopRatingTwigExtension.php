<?php

namespace App\Twig\Extension;

class WorkshopRatingTwigExtension extends \Twig\Extension\AbstractExtension
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
        $str = '<span style="width: 100px; display: inline-block">';

        if($rating < 0.25){
            $str .= self::STAR_NONE . self::STAR_NONE . self::STAR_NONE . self::STAR_NONE . self::STAR_NONE;
        } elseif($rating < 0.75){
            $str .= self::STAR_HALF . self::STAR_NONE . self::STAR_NONE . self::STAR_NONE . self::STAR_NONE;
        } elseif($rating < 1.25){
            $str .= self::STAR_FULL . self::STAR_NONE . self::STAR_NONE . self::STAR_NONE . self::STAR_NONE;
        } elseif($rating < 1.75){
            $str .= self::STAR_FULL . self::STAR_HALF . self::STAR_NONE . self::STAR_NONE . self::STAR_NONE;
        } elseif($rating < 2.25){
            $str .= self::STAR_FULL . self::STAR_FULL . self::STAR_NONE . self::STAR_NONE . self::STAR_NONE;
        } elseif($rating < 2.75){
            $str .= self::STAR_FULL . self::STAR_FULL . self::STAR_HALF . self::STAR_NONE . self::STAR_NONE;
        } elseif($rating < 3.25){
            $str .= self::STAR_FULL . self::STAR_FULL . self::STAR_FULL . self::STAR_NONE . self::STAR_NONE;
        } elseif($rating < 3.75){
            $str .= self::STAR_FULL . self::STAR_FULL . self::STAR_FULL . self::STAR_HALF . self::STAR_NONE;
        } elseif($rating < 4.25){
            $str .= self::STAR_FULL . self::STAR_FULL . self::STAR_FULL . self::STAR_FULL . self::STAR_NONE;
        } elseif($rating < 4.75){
            $str .= self::STAR_FULL . self::STAR_FULL . self::STAR_FULL . self::STAR_FULL . self::STAR_HALF;
        } else {
            $str .= self::STAR_FULL . self::STAR_FULL . self::STAR_FULL . self::STAR_FULL . self::STAR_FULL;
        }

        $str .= '</span>';

        return $str;
    }
}
