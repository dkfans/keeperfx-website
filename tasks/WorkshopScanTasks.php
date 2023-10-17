<?php

$schedule = new \Crunz\Schedule();

$task = $schedule->run(\PHP_BINARY . ' ' . \dirname(__DIR__) . '/console clamav:scan-workshop-new');
$task
    ->everyMinute()
    ->description('Scan new workshop files for malware')
    ->preventOverlapping();

$task2 = $schedule->run(\PHP_BINARY . ' ' . \dirname(__DIR__) . '/console clamav:scan-workshop-all');
$task2
    ->daily()
    ->description('Scan all workshop files for malware')
    ->preventOverlapping();

return $schedule;
