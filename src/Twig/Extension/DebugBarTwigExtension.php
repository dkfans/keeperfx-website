<?php

namespace App\Twig\Extension;

use DebugBar\StandardDebugBar;
use DebugBar\JavascriptRenderer;


class DebugBarTwigExtension extends \Twig\Extension\AbstractExtension
{

    private JavascriptRenderer $js_renderer;

    public function __construct(StandardDebugBar $debug_bar)
    {
        $this->js_renderer = $debug_bar->getJavascriptRenderer(APP_ROOT_URL . '/assets/debugbar');
    }

    public function getName(): string
    {
        return 'debug_bar_extension';
    }

    public function getFunctions(): array
    {
        return [
            new \Twig\TwigFunction(
                'debug_bar_render_head',
                [$this, 'debugBarRenderHead'],
                ['is_safe' => ['all']]
            ),
            new \Twig\TwigFunction(
                'debug_bar_render_body',
                [$this, 'debugBarRenderBody'],
                ['is_safe' => ['all']]
            ),
        ];
    }

    public function debugBarRenderHead(): string
    {
        return $this->js_renderer->renderHead();
    }

    public function debugBarRenderBody(): string
    {
        return $this->js_renderer->render();
    }
}
