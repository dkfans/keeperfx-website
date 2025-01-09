<?php

namespace App\Twig\Extension;

use Twig\TwigFilter;

class GithubInteractTwigExtension extends \Twig\Extension\AbstractExtension
{

    public function getName(): string
    {
        return 'github_interact_extension';
    }

    public function getFilters()
    {
        return [
            new TwigFilter('github_interact', [$this, 'githubInteract'], ['is_safe' => ['all']]),
        ];
    }

    /**
     * Edit the content and add Github interaction links.
     *
     * @param string $content
     * @return string
     */
    public function githubInteract(string $content): string
    {
        // Remove invalid issue strings
        $content = \preg_replace('/\s*(\(.*?\…)/', '…', $content);

        // Convert to link
        $replacement = '<a href="https://github.com/dkfans/keeperfx/issues/$1" target="_blank">#$1</a>';
        $content = \preg_replace('/\#(\d{1,6})/', $replacement, $content);

        // Return
        return $content;
    }
}
