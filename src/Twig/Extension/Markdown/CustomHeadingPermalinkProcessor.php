<?php

declare(strict_types=1);

namespace App\Twig\Extension\Markdown;

use League\CommonMark\Node\Node;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Node\StringContainerInterface;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalink;

class CustomHeadingPermalinkProcessor
{
    public function onDocumentParsed(DocumentParsedEvent $event): void
    {
        $walker = $event->getDocument()->walker();

        while ($walkerEvent = $walker->next()) {
            $node = $walkerEvent->getNode();
            if ($node instanceof Heading && $walkerEvent->isEntering()) {
                $slug = $this->generateSlug($this->getHeadingText($node));
                $node->data->set('attributes/id', $slug);

                // Add permalink
                $this->addPermalink($node, $slug);
            }
        }
    }

    private function generateSlug(string $text): string
    {
        // Preserve underscores and hyphens, remove other special characters
        $slug = \strtolower($text);
        $slug = \str_replace(' ', '-', $slug);
        $slug = \preg_replace('/[^A-Za-z0-9_-]/', '', $slug);
        return $slug;
    }

    private function getHeadingText(Node $node): string
    {
        $text = '';
        foreach ($node->children() as $child) {
            if ($child instanceof StringContainerInterface) {
                $text .= $child->getLiteral();
            }
        }
        return $text;
    }

    private function addPermalink(Heading $heading, string $slug): void
    {
        $permalink = new HeadingPermalink($slug, '🔗', ['class' => 'header-link']);
        $heading->appendChild($permalink);
    }
}
