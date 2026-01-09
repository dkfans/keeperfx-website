<?php

declare(strict_types=1);

namespace App\Twig\Extension\Markdown;

use AMoschou\CommonMark\Alert\AlertExtension;
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
        $environment->addExtension(new AlertExtension());
    }

    public function convert(string $string): string
    {
        // Do the normal conversion
        $html = $this->converter->convert($string)->getContent();

        // Do our custom conversions
        $html = $this->processSpoilerTags($html);
        $html = $this->processYouTubeTags($html);

        // Return the html directly
        return $html;
    }

    private function processSpoilerTags(string $content): string
    {
        return \preg_replace(
            '~\|\|(.+?)\|\|~',
            '<span class="spoiler spoiler-hover">$1</span>',
            $content
        );
    }

    private function processYouTubeTags(string $content): string
    {
        return \preg_replace_callback(
            '/\[\[youtube:(.+?)\]\]/',
            function (array $matches): string {
                $url = trim($matches[1]);
                if (\preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|v/|shorts/))([\w\-]{11})~', $url, $ytMatch)) {
                    $id = htmlspecialchars($ytMatch[1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    return sprintf(
                        '<div class="youtube-wrapper" data-video-id="%s">
                            <noscript>
                                <span class="youtube-wrapper-javascript-warning">You need to enable javascript for the YouTube embed to work</span>
                            </noscript>
                        </div>',
                        $id,
                        $id
                    );
                }
                return '';
            },
            $content
        );
    }
}
