<?php

declare(strict_types=1);

namespace App\Twig\Extension\Markdown;

use Nette\Schema\Expect;
use League\Config\ConfigurationBuilderInterface;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\ConfigurableExtensionInterface;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkProcessor;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkRenderer;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalink;

class CustomHeadingPermalinkExtension implements ConfigurableExtensionInterface
{
    public function configureSchema(ConfigurationBuilderInterface $builder): void
    {
        $builder->addSchema('heading_permalink', Expect::structure([
            'min_heading_level' => Expect::int()->min(1)->max(6)->default(1),
            'max_heading_level' => Expect::int()->min(1)->max(6)->default(6),
            'insert' => Expect::anyOf(HeadingPermalinkProcessor::INSERT_BEFORE, HeadingPermalinkProcessor::INSERT_AFTER, HeadingPermalinkProcessor::INSERT_NONE)->default(HeadingPermalinkProcessor::INSERT_BEFORE),
            'id_prefix' => Expect::string()->default('content'),
            'apply_id_to_heading' => Expect::bool()->default(false),
            'heading_class' => Expect::string()->default(''),
            'fragment_prefix' => Expect::string()->default('content'),
            'html_class' => Expect::string()->default('heading-permalink'),
            'title' => Expect::string()->default('Permalink'),
            'symbol' => Expect::string()->default(HeadingPermalinkRenderer::DEFAULT_SYMBOL),
            'aria_hidden' => Expect::bool()->default(true),
        ]));
    }

    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addRenderer(HeadingPermalink::class, new HeadingPermalinkRenderer());

        $environment->addEventListener(DocumentParsedEvent::class, [new CustomHeadingPermalinkProcessor(), 'onDocumentParsed']);
    }
}
