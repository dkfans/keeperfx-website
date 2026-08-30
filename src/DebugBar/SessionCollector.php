<?php

namespace App\DebugBar;

use Compwright\PhpSession\Session;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use Psr\Container\ContainerInterface;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;

// 2. Implement the interface
class SessionCollector extends DataCollector implements Renderable
{
    public function __construct(
        private ContainerInterface $container,
    ) {
    }

    public function collect(): array
    {
        $data   = [];
        $cloner = new VarCloner();
        $dumper = new CliDumper();

        foreach ($this->container->get(Session::class)->toArray() as $key => $val) {
            $output = '';

            // Dump into an open stream/memory buffer instead of stdout
            $dumper->dump($cloner->cloneVar($val), static function ($line, $depth) use (&$output) {
                if ($depth >= 0) {
                    $output .= \str_repeat('  ', $depth) . $line . "\n";
                }
            });

            // Fallback or trimmed string representation
            $data[$key] = \trim($output) !== '' ? \trim($output) : $val;
        }

        return $data;
    }

    public function getName(): string
    {
        return 'session';
    }

    public function getWidgets(): array
    {
        return [
            'session' => [
                'icon'    => 'tags',
                'widget'  => 'PhpDebugBar.Widgets.VariableListWidget',
                'map'     => 'session',
                'default' => '{}',
                'badge'   => \count($this->container->get(Session::class)),
            ],
        ];
    }
}
