<?php

namespace App\Helper;

class SlugHelper
{
    public static function slugify(string $text): string
    {
        $string = $text;

        if (\function_exists('transliterator_transliterate')) {

            // Fancy Transliterator
            // Even translates Japanese into ASCII/Latin
            $string = \transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $string);

        } elseif (\function_exists('iconv')) {

            // iconv
            $string = \iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $string);

        } else {

            // Simple fallback using a map
            $map = [
                'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'AE', 'Ç' => 'C',
                'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
                'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U',
                'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'ß' => 'ss', 'à' => 'a', 'á' => 'a', 'â' => 'a',
                'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'ae', 'ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e',
                'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o',
                'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
                'ý' => 'y', 'ÿ' => 'y',
            ];

            // Translate known characters
            $string = \strtr($string, $map);

            // Strip any remaining non-ASCII characters that weren't caught
            $string = \preg_replace('/[^\x20-\x7E]/u', '', $string);
        }

        $string = \strtolower($string);

        $string = \preg_replace('/[^a-z0-9]+/', '-', $string);

        // Replace multiple dashes
        $string = \preg_replace('/-+/', '-', $string);

        $string = \trim($string, '-');

        return $string;
    }
}
