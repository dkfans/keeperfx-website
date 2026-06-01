<?php

namespace App;

use App\Config\Config;
use Psr\Log\LoggerInterface;
use Xenokore\Utility\Helper\FileHelper;

class CDN
{
    // Database: https://cdn.jsdelivr.net/npm/@ip-location-db/geo-whois-asn-country-mmdb/geo-whois-asn-country.mmdb

    private array $cdn_config;

    private string $current_cdn_id;
    private array $current_cdn;

    private bool $is_user_choice = false;

    public function __construct()
    {
        $this->cdn_config = Config::load('cdn');

        $this->current_cdn_id = $this->cdn_config['default'];
        $this->current_cdn = $this->cdn_config['endpoints'][$this->current_cdn_id];
    }

    public function setCdn(?string $cdn_id)
    {
        if ($cdn_id === null) {
            $this->setCdn($this->cdn_config['default']);
            return;
        }

        if ($this->current_cdn_id === $cdn_id) {
            return;
        }

        // Check if this CDN id exists
        if (\array_key_exists($cdn_id, $this->cdn_config['endpoints']) === false) {
            // We just set it to the default because people might have specifically selected this one and
            // we don't want to throw an error or anything.
            $this->setCdn($this->cdn_config['default']);
            return;
        }

        $this->current_cdn_id = $cdn_id;
        $this->current_cdn = $this->cdn_config['endpoints'][$cdn_id];
    }

    public function setByCountryDefault(string $country): bool
    {
        if (\array_key_exists($country, $this->cdn_config['country_default'])) {
            $this->setCdn($this->cdn_config['country_default'][$country]);
            return true;
        }

        return false;
    }

    public function getCurrentId(): ?string
    {
        return $this->current_cdn_id;
    }

    public function getCurrent(): array
    {
        return $this->current_cdn;
    }

    public function getBaseUrl(): ?string
    {
        return $this->current_cdn['url'];
    }

    public function setUserChoice(?bool $is_user_choice = true)
    {
        $this->is_user_choice = $is_user_choice;
    }

    public function isUserChoice(): bool
    {
        return $this->is_user_choice;
    }

    public function getAll(): array
    {
        return $this->cdn_config['endpoints'];
    }

    public function isValidCdn(string $name): bool
    {
        return \array_key_exists($name, $this->getAll());
    }
}
