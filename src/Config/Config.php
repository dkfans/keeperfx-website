<?php

namespace App\Config;

use Xenokore\Utility\Helper\ArrayHelper;
use Xenokore\Utility\Helper\FileHelper;

use App\Config\ConfigException;
use Xenokore\Utility\Exception\ArrayKeyNotFoundException;

class Config {

    private const CONFIG_DIR       = APP_ROOT . '/config';

    private static array $configs = [];

    /**
     * Get a config value using dot notation.
     * Example: 'app.app_name'
     *
     * @param string $variable
     * @return mixed
     */
    public static function get(string $variable): mixed
    {
        try {
            return ArrayHelper::get(self::$configs, $variable, null, true);
        } catch (ArrayKeyNotFoundException $ex){
            throw new ConfigException("Config value not found: '{$variable}'");
        }
    }

    public static function load(string $config_name): array
    {
        if(isset(self::$configs[$config_name])){
            return self::$configs[$config_name];
        }

        $path = self::CONFIG_DIR . '/' . $config_name . '.config.php';

        return self::loadFile($path);
    }

    public static function loadFile(string $path): array
    {
        $filename    = \basename($path);
        $config_name = \substr($filename, 0, \strlen($filename) - \strlen('.config.php'));

        if(isset(self::$configs[$config_name])){
            return self::$configs[$config_name];
        }

        if(!FileHelper::isAccessible($path)){
            throw new ConfigException("Config file is not accessible: '{$path}'");
        }

        $config = require $path;

        if(!\is_array($config)){
            throw new ConfigException("Invalid config file: '{$path}'. The file must return an array.");
        }

        self::$configs[$config_name] = $config;

        return $config;
    }

    public static function loadDir(string $path): array
    {
        $configs = [];
        $path    = \realpath($path);

        foreach(\glob($path . '/*.config.php') as $config_file){

            $filename    = \basename($config_file);
            $config_name = \substr($filename, 0, \strlen($filename) - \strlen('.config.php'));

            $configs[$config_name] = self::loadFile($config_file);
        }

        return $configs;
    }

}
