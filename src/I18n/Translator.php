<?php

namespace App\I18n;

use App\I18n\Exception\TranslatorException;
use Xenokore\Utility\Helper\ArrayHelper;
use Xenokore\Utility\Helper\FileHelper;

class Translator {

    public const I18N_DIR = APP_ROOT . '/i18n';

    public const NOT_FOUND_STRING = '[[ %s ]]';

    private Locale $locale;

    private array $translations = [];

    public function __construct(Locale $locale)
    {
        $this->locale = $locale;
    }

    public function translate(string $key, ...$vars)
    {

        // Check if category + translation key is set
        if(\strpos($key, '.') === false){
            throw new TranslatorException("Invalid translation key: '{$key}'. Only a category is supplied.");
        }

        $category    = \explode('.', $key)[0];
        $locale_code = $this->locale->getCode();

        // Try and load category file if it's not loaded yet
        if(!isset($this->translations[$locale_code][$category])){
            $this->loadTranslationFile($locale_code, $category);
        }

        // Handle translation if category is loaded
        if(isset($this->translations[$locale_code][$category])){
            $translation = $this->getTranslation($key, ...$vars);
            if($translation !== null){
                return $translation;
            }
        }

        // Return not found
        return \sprintf(self::NOT_FOUND_STRING, $key);
    }

    private function getTranslation(string $key, ...$vars): string|null
    {
        $translation = ArrayHelper::get($this->translations[$this->locale->getCode()], $key);

        // Check if translation is found
        if(\is_null($translation)){
            return null;
        }

        // Check if translation is valid
        if(!\is_string($translation) && !is_array($translation)){
            throw new TranslatorException(
                "Translation key '{$key}' must resolve to a string or an array (pluralization)."
            );
        }

        // Handle normal translation strings
        if(is_string($translation)){
            return \sprintf($translation, ...$vars);
        }

        // Handle pluralization
        // When the translation key resolves an array
        if(\is_array($translation)){

            // Check for default pluralization index
            if(!isset($translation['_'])){
                throw new TranslatorException(
                    "Translation key '{$key}' resolves an array (pluralization) but does not contain the \"_\" (default pluralize) index."
                );
            }

            // Get the first number out of the variables
            // To make sure a number is given
            $number = null;
            foreach($vars as $val){
                if(\is_int($val) || \is_numeric($val)){
                    $number = $val;
                    break;
                }
            }
            if($number === null){
                throw new TranslatorException(
                    "Translation key '{$key}' needs one of its given values to be a number for pluralization."
                );
            }

            // Check if number for pluralization exists (needs to be an int)
            $number_int = (int)$number;
            if(isset($translation[$number_int])){
                return \sprintf($translation[$number_int], ...$vars);
            } else {
                return \sprintf($translation['_'], ...$vars);
            }

        }

        // Not found (not reachable)
        return null;
    }

    private function loadTranslationFile(string $locale_code, string $category)
    {
        $path = self::I18N_DIR . "/{$locale_code}/translations/{$category}.{$locale_code}.translation.php";

        if(!\file_exists($path)){
            return false;
        }

        if(!FileHelper::isAccessible($path)){
            throw new TranslatorException("Translation file exists but is not accessible: '{$path}'");
        }

        $translations = require $path;

        if(!\is_array($translations)){
            throw new TranslatorException("Invalid translation file: '{$path}'. Must return an array.");
        }

        $this->translations[$locale_code][$category] = $translations;

        return true;
    }


    /**
     * Get the locale
     *
     * @return Locale
     */
    public function getLocale(): Locale
    {
        return $this->locale;
    }

    /**
     * Set the locale
     *
     * @param Locale $locale
     * @return self
     */
    public function setLocale(Locale $locale): self
    {
        $this->locale = $locale;

        return $this;
    }
}
