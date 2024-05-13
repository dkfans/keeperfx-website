<?php

namespace App;

use Xenokore\Utility\Helper\StringHelper;

class SpamDetector {

    private $blacklisted_words = [];

    public function __construct()
    {
        // Load our blacklist
        $this->blacklisted_words = include APP_ROOT . '/config/spam.words.config.php';
    }

    public function detectSpam(string $string): bool
    {
        // Make the string lowercase
        $string = \strtolower($string);

        // Check if the string contains any of the words
        foreach($this->blacklisted_words as $word){
            if(StringHelper::contains($string, $word)){
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a string has emojis
     *
     * Source: https://stackoverflow.com/a/71559156
     *
     * @param string $string
     * @return boolean
     */
    public function detectEmojis(string $string): bool
    {
        $emojis_regex =
            '/[\x{0080}-\x{02AF}'
            .'\x{0300}-\x{03FF}'
            .'\x{0600}-\x{06FF}'
            .'\x{0C00}-\x{0C7F}'
            .'\x{1DC0}-\x{1DFF}'
            .'\x{1E00}-\x{1EFF}'
            .'\x{2000}-\x{209F}'
            .'\x{20D0}-\x{214F}'
            .'\x{2190}-\x{23FF}'
            .'\x{2460}-\x{25FF}'
            .'\x{2600}-\x{27EF}'
            .'\x{2900}-\x{29FF}'
            .'\x{2B00}-\x{2BFF}'
            .'\x{2C60}-\x{2C7F}'
            .'\x{2E00}-\x{2E7F}'
            .'\x{3000}-\x{303F}'
            .'\x{A490}-\x{A4CF}'
            .'\x{E000}-\x{F8FF}'
            .'\x{FE00}-\x{FE0F}'
            .'\x{FE30}-\x{FE4F}'
            .'\x{1F000}-\x{1F02F}'
            .'\x{1F0A0}-\x{1F0FF}'
            .'\x{1F100}-\x{1F64F}'
            .'\x{1F680}-\x{1F6FF}'
            .'\x{1F910}-\x{1F96B}'
            .'\x{1F980}-\x{1F9E0}]/u';
        \preg_match($emojis_regex, $string, $matches);
        return !empty($matches);
    }

}
