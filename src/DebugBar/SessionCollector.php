<?php

namespace App\DebugBar;

use Compwright\PhpSession\Session;
use DebugBar\DataCollector\Renderable;
use DebugBar\DataCollector\DataCollector;
use Psr\Container\ContainerInterface;

// 2. Implement the interface
class SessionCollector extends DataCollector implements Renderable
{
    public function __construct(
        private ContainerInterface $container,
    ) {}

    public function collect()
    {
        $data = [];

        foreach ($this->container->get(Session::class)->toArray() as $key => $val) {
            $data[$key] = $this->formatVar($val);
        }

        return $data;
    }

    public function getName()
    {
        return 'session';
    }

    public function getWidgets()
    {
        return [
            "session" => [
                "icon" => "tags",
                "widget" => "PhpDebugBar.Widgets.VariableListWidget",
                "map" => "session",
                "default" => "{}",
                "badge" => \count($this->container->get(Session::class))
            ],
        ];
    }
}
