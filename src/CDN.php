<?php

namespace App;

use Symfony\Component\Yaml\Yaml;

class CDN
{
    private array $cdn_config;

    private string $current_cdn_id;
    private array $current_cdn;

    private bool $is_user_choice = false;

    private function getDefaultConfig(): array
    {
        return [
            'endpoints' => [
                '_self' => [
                    'name' => \str_replace(
                        'keeperfx',
                        'KeeperFX',
                        \parse_url($_ENV['APP_ROOT_URL'], \PHP_URL_HOST) . (\parse_url($_ENV['APP_ROOT_URL'], \PHP_URL_PORT) ? ':' . \parse_url($_ENV['APP_ROOT_URL'], \PHP_URL_PORT) : '')
                    ),
                    'url'      => $_ENV['APP_ROOT_URL'],
                    'location' => 'Unknown',
                ],
            ],
            'show_self_endpoint' => true,
            'default_endpoint'   => '_self',
            'country_defaults'   => [],
        ];
    }

    public function __construct()
    {
        // Load the default config
        $this->cdn_config = $this->getDefaultConfig();

        // Load the YAML config
        $yaml_config_path = \APP_ROOT . '/cdn.config.yml';
        if (\file_exists($yaml_config_path)) {
            $this->loadYamlConfig($yaml_config_path);
        }

        // Set the current CDN based on the default
        $this->current_cdn_id = $this->cdn_config['default_endpoint'];
        $this->current_cdn    = $this->cdn_config['endpoints'][$this->current_cdn_id];
    }

    public function loadYamlConfig(string $path): void
    {
        // Parse the YAML config file
        $yaml = Yaml::parseFile($path);

        // Load values into our loaded config
        if (\is_array($yaml)) {
            if (isset($yaml['endpoints']) && \is_array($yaml['endpoints'])) {
                $this->cdn_config['endpoints'] = \array_merge($this->cdn_config['endpoints'], $yaml['endpoints']);
            }
            if (isset($yaml['default_endpoint'])) {
                $this->cdn_config['default_endpoint'] = $yaml['default_endpoint'];
            }
            if (isset($yaml['show_self_endpoint'])) {
                $this->cdn_config['show_self_endpoint'] = $yaml['show_self_endpoint'];
            }
            if (isset($yaml['country_defaults']) && \is_array($yaml['country_defaults'])) {
                $this->cdn_config['country_defaults'] = $yaml['country_defaults'];
            }
        }
    }

    public function setCdn(?string $cdn_id): void
    {
        if ($cdn_id === null) {
            $this->setCdn($this->cdn_config['default_endpoint']);

            return;
        }

        if ($this->current_cdn_id === $cdn_id) {
            return;
        }

        // Check if this CDN id exists
        if (\array_key_exists($cdn_id, $this->cdn_config['endpoints']) === false) {
            // We just set it to the default because people might have specifically selected this one and
            // we don't want to throw an error or anything.
            $this->setCdn($this->cdn_config['default_endpoint']);

            return;
        }

        $this->current_cdn_id = $cdn_id;
        $this->current_cdn    = $this->cdn_config['endpoints'][$cdn_id];
    }

    public function setByCountryDefault(string $country): bool
    {
        $country = \strtoupper($country);

        if (\array_key_exists($country, $this->cdn_config['country_defaults'])) {
            $this->setCdn($this->cdn_config['country_defaults'][$country]);

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

    public function setUserChoice(?bool $is_user_choice = true): void
    {
        $this->is_user_choice = $is_user_choice;
    }

    public function isUserChoice(): bool
    {
        return $this->is_user_choice;
    }

    public function getAll(): array
    {
        $endpoints = $this->cdn_config['endpoints'];

        if ($this->cdn_config['show_self_endpoint'] !== true && isset($endpoints['_self'])) {
            unset($endpoints['_self']);
        }

        return $endpoints;
    }

    public function isValidCdn(string $name): bool
    {
        return \array_key_exists($name, $this->getAll());
    }
}
