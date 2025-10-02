<?php

namespace App\Twig\Extension;

use Twig\TwigFilter;
use Twig\Extension\AbstractExtension;
use App\Twig\Extension\Markdown\CustomMarkdownConverter;

class StripMarkdownExtension extends AbstractExtension
{

    public function __construct(
        private CustomMarkdownConverter $md_converter,
    ) {}

    public function getName(): string
    {
        return 'strip_markdown_extension';
    }

    public function getFilters()
    {
        return [
            new TwigFilter('strip_markdown', [$this, 'stripMarkdown']),
        ];
    }

    /**
     * Strip the markdown from the string
     *
     * @param string $string
     * @return string
     */
    public function stripMarkdown(string $string): string
    {
        $string = $this->md_converter->convert($string);

        // Normalize line endings
        $string = preg_replace("/\r\n?/", "\n", $string);

        // Replace block tags with double newlines
        $string = preg_replace('#<\s*(p|div|section|article|header|footer|aside)[^>]*>#i', "\n\n", $string);

        // Replace <br> with single newline
        $string = preg_replace('#<\s*br\s*/?>#i', "\n", $string);

        // Strip remaining tags
        $text = strip_tags($string);

        // Clean up whitespace
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/ *\n */', "\n", $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text);

        return trim($text);
    }
}
