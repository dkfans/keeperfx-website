<?php

declare(strict_types=1);

namespace App\Twig\Extension\Markdown;

use Twig\Extra\Markdown\MarkdownRuntime;
use Twig\RuntimeLoader\RuntimeLoaderInterface;

class CustomMarkdownRuntimeLoader implements RuntimeLoaderInterface
{
    public function load($class)
    {
        if ($class === MarkdownRuntime::class) {
            return new MarkdownRuntime(new CustomMarkdownConverter());
        }

        return null;
    }
}
