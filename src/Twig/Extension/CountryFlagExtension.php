<?php

namespace App\Twig\Extension;

class CountryFlagExtension extends \Twig\Extension\AbstractExtension implements \Twig\Extension\GlobalsInterface
{

    private array $country_names  = [];
    private array $country_emojis = [];

    public function __construct(){
        $this->country_names  = (include APP_ROOT . '/config/country.name.config.php');
        $this->country_emojis = (include APP_ROOT . '/config/country.flag.config.php');
    }

    public function getName(): string
    {
        return 'country_flag_extension';
    }

    public function getFunctions(): array
    {
        return [
            new \Twig\TwigFunction(
                'get_country_string',
                [$this, 'getCountryString'],
                ['is_safe' => ['html']]
            ),
            new \Twig\TwigFunction(
                'get_country_emoji',
                [$this, 'getCountryEmoji'],
                ['is_safe' => ['html']]
            ),
        ];
    }

    public function getGlobals(): array
    {
        return [
            'country_codes' => \array_keys($this->country_names)
        ];
    }

    public function getCountryString(string $country_code){
        return $this->country_names[$country_code] ?? $country_code;
    }

    public function getCountryEmoji(string $country_code){
        return $this->country_emojis[$country_code] ?? '';
    }
}
