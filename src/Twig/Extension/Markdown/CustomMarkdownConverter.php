<?php

declare(strict_types=1);

namespace App\Twig\Extension\Markdown;

use Twig\Extra\Markdown\MarkdownInterface;
use League\CommonMark\GithubFlavoredMarkdownConverter;

class CustomMarkdownConverter implements MarkdownInterface
{
    private $converter;

    public function __construct()
    {
        $this->converter = new GithubFlavoredMarkdownConverter([
            'heading_permalink' => [
                'html_class' => 'header-link',
                'id_prefix' => '',
                'fragment_prefix' => '',
                'insert' => 'after',
                'title' => 'Permalink',
                'symbol' => '🔗',
                'apply_id_to_heading' => true,
            ]
        ]);

        $environment = $this->converter->getEnvironment();
        $environment->addExtension(new CustomHeadingPermalinkExtension());
    }

    public function convert(string $string): string
    {
        $string = $this->processSpoilerTags($string);
        return $this->converter->convert($string)->getContent();
    }

    private function processSpoilerTags(string $content): string
    {
        return \preg_replace(
            '~\|\|(.+?)\|\|~',
            '<span class="spoiler spoiler-hover">$1</span>',
            $content
        );
    }
}
