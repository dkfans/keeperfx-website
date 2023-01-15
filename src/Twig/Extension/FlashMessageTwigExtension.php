<?php

namespace App\Twig\Extension;

use App\FlashMessage;

class FlashMessageTwigExtension extends \Twig\Extension\AbstractExtension
{
    /** @var FlashMessage $flash */
    private $flash;

    /**
     * Bootstrap alert index
     *
     * @var integer
     */
    private $index = 0;

    public function __construct(FlashMessage $flash)
    {
        $this->flash = $flash;
    }

    public function getName(): string
    {
        return 'flash_message_extension';
    }

    public function getFunctions(): array
    {
        return [
            new \Twig\TwigFunction(
                'show_flash_message',
                [$this, 'renderFlashMessageHtml'],
                ['is_safe' => ['html']] // Allow raw HTML output
            ),
            new \Twig\TwigFunction(
                'render_flash_messages',
                [$this, 'renderFlashMessages'],
                ['is_safe' => ['html']] // Allow raw HTML output
            ),
            new \Twig\TwigFunction(
                'has_flash_messages',
                [$this, 'hasFlashMessages']
            ),
        ];
    }

    public function renderFlashMessageHtml(string $type, string $message): string
    {
        $str = '<div class="alert alert-solid alert-' . $type;
        $str .= '" role="alert" data-alert-index="' . $this->index++ . '">';
        $str .= $message . '</div>';
        return $str;
    }

    /**
     * Render all flash messages which have not been displayed yet
     *
     * @return string
     */
    public function renderFlashMessages(): string
    {
        $string = '';

        foreach ($this->flash->getAll() as $message) {
            if ($message['type'] == 'error') {
                $message['type'] = 'danger';
            }

            $string .= $this->renderFlashMessageHtml($message['type'], $message['message']);
        }

        return $string;
    }

    public function hasFlashMessages(): bool
    {
        return $this->flash->hasMessage();
    }
}
