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

}
