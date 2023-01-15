<?php

return [
    'commands' => [
        new \Psy\Command\ParseCommand,
    ],

    'defaultIncludes' => [
        __DIR__ . '/app/bootstrap/bootstrap.psysh.php'
    ],

    // 'startupMessage' => 'PsySH REPL',

    // 'prompt' => 'repl> '
];
