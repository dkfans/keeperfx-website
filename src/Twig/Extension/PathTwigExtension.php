<?php

namespace App\Twig\Extension;

use Psr\Http\Message\ServerRequestInterface as Request;

class PathTwigExtension extends \Twig\Extension\AbstractExtension
{
    /**
     * The current path
     * @var string
     */
    private $current_path = '/';

    public function __construct()
    {
        if(!empty($_SERVER["REQUEST_URI"])){
            $this->current_path = \parse_url($_SERVER["REQUEST_URI"])['path'];
        }
    }

    public function getName(): string
    {
        return 'path_extension';
    }

    public function getFunctions(): array
    {
        return [
            new \Twig\TwigFunction('path_equals', [$this, 'pathEquals']),
        ];
    }

    /**
     * Check the if a given path matches the current path.
     * Wildcards can be used (ex: "/users/*"), as well as Regexes
     *
     * @param string ...$paths
     * @return boolean
     */
    public function pathEquals(string ...$paths): bool
    {
        foreach ($paths as $path) {
            if (strpos($path, '+') !== false || strpos($path, '[') !== false) {
                $regex_path = str_replace('/', '\\/', addslashes($path));
                $regex_path = '~^' . $regex_path . '$~';

                if (preg_match($regex_path, $this->current_path)) {
                    return true;
                }
            }
            if (fnmatch($path, $this->current_path)) {
                return true;
            }
            if ($path === $this->current_path) {
                return true;
            }
        }

        return false;
    }
}
