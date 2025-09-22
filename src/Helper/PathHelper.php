<?php

namespace App\Helper;

use Xenokore\Utility\Helper\StringHelper;

class PathHelper
{
    public const CLI_PATH_AFFIX = '_CLI_PATH';

    /**
     * Get a specific file path using the ENV vars.
     *
     * This function allows us to append `_CLI_PATH` to the ENV var name to
     * load a different path when using the CLI.
     * This is useful if the web app lives in a **chroot**.
     *
     * @param string $env_var_name
     * @return string|null
     */
    public static function getAppPathFromEnvVar(string $env_var_name): ?string
    {
        // Make sure the var name doesn't already end with 'CLI_PATH'
        if (StringHelper::endsWith($env_var_name, self::CLI_PATH_AFFIX)) {
            throw new \Exception('getAppPathFromEnvVar should be called on the non CLI path');
        }

        $path = null;

        // If running php from the command line
        if (\php_sapi_name() === 'cli' || \defined('STDIN')) {

            // Return the variable's CLI path if it exists
            $env_var_cli_name = $env_var_name . self::CLI_PATH_AFFIX;
            if (!empty($_ENV[$env_var_cli_name])) {
                return $_ENV[$env_var_cli_name];
            }
        }

        // Return the default variable's path
        if (!empty($_ENV[$env_var_name])) {
            return $_ENV[$env_var_name];
        }

        return null;
    }
}
