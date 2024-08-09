<?php

namespace App;

class Theme
{
    private string $current_theme_id = 'default';

    private array $theme_config;

    private array $themes;

    public function __construct(){
        // Load the theme config
        $this->theme_config = include APP_ROOT . '/config/theme.config.php';

        // Load the themes
        foreach($this->theme_config['themes'] as $theme_id => $theme_file)
        {
            // Make sure it is lowercase
            $theme_id = \strtolower($theme_id);

            // Get a nice name for this theme based on the ID
            // Ex: modern_black => Modern Black
            // TODO: translations
            $theme_name = \implode(' ', \preg_split('/(?=[A-Z])/', $theme_id));
            $theme_name = \str_replace(['_', '-'], [' ', ' '], $theme_name);
            $theme_name = \preg_replace('/[\s]+/mu', ' ', $theme_name);
            $theme_name = \ucwords($theme_name);

            // Remember theme data
            $this->themes[$theme_id] = [
                'id'         => $theme_id,
                'name'       => $theme_name,
                'stylesheet' => $theme_file,
            ];
        }
    }

    public function setTheme(string $theme_id): bool
    {
        $theme_id = \strtolower($theme_id);
        if(\in_array($theme_id, \array_keys($this->themes))){
            $this->current_theme_id = $theme_id;
            return true;
        }

        return false;
    }

    public function getCurrentTheme(): array
    {
        return $this->themes[$this->current_theme_id];
    }

    public function getAllThemes(): array
    {
        return $this->themes;
    }
}
