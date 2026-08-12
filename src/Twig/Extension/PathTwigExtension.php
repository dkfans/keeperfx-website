<?php

namespace App\Twig\Extension;

use Psr\Http\Message\ServerRequestInterface as Request;

class PathTwigExtension extends \Twig\Extension\AbstractExtension
{
    /**
     * The current uri.
     *
     * @var string
     */
    private $current_uri = '/';

    /**
     * The current path.
     *
     * @var string
     */
    private $current_path = '/';

    public function __construct()
    {
        if (!empty($_SERVER['REQUEST_URI'])) {
            $this->current_uri  = $_SERVER['REQUEST_URI'];
            $this->current_path = \parse_url($_SERVER['REQUEST_URI'])['path'] ?? '/';
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
            new \Twig\TwigFunction('get_path', [$this, 'getPath']),
        ];
    }

    /**
     * Check the if a given path matches the current path.
     * Wildcards can be used (ex: "/users/*"), as well as Regexes.
     */
    public function pathEquals(string ...$paths): bool
    {
        foreach ([$this->current_uri, $this->current_path] as $current) {

            // Make sure the current URL is not too long
            // 4096 is the hard limit for the fnmatch() function anyways
            if (\strlen($current) > 4096) {
                return false;
            }

            foreach ($paths as $path) {

                if (\str_contains($path, '+') || \str_contains($path, '[')) {
                    $regex_path = \str_replace('/', '\\/', \addslashes($path));
                    $regex_path = '~^' . $regex_path . '$~';

                    if (\preg_match($regex_path, $current)) {
                        return true;
                    }
                }
                if (\fnmatch($path, $current)) {
                    return true;
                }
                if ($path === $current) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get the current server request PATH.
     */
    public function getPath(): string
    {
        return $this->current_path;
    }
}
