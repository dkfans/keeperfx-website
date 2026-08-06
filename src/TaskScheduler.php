<?php

namespace App;

class TaskScheduler
{

    public static function schedule(array ...$tasks)
    {
        // Get app root, which is needed to get an absolute path to the 'console' script
        $app_root = dirname(__DIR__);

        // Start a Crunz scheduler
        $schedule = new \Crunz\Schedule();

        // Loop trough all the given tasks and add them to the scheduler
        foreach ($tasks as $task) {

            // Create the Crunz task
            $crunz_task = $schedule->run(\PHP_BINARY . ' ' . $app_root . '/console ' . $task['console_command']);

            // Set interval
            $crunz_task->{$task['interval']}();

            // Set description
            $crunz_task->description($task['description']);

            // Disable overlapping if set
            if ($task['prevent_overlapping']) {
                $crunz_task->preventOverlapping();
            }

            // Log output
            // Use a backtrace to figure out the filename of the script that created the task and use it as the log name
            $backtrace = debug_backtrace();
            $log_name = (isset($backtrace[1]['file']) ? basename($backtrace[1]['file'], '.php') : 'UnknownTasks') . '.log';
            $log_filepath = ($_ENV['APP_LOG_STORAGE'] ?? '/app/log') . $log_name;
            $crunz_task->appendOutputTo($log_filepath);
        }

        return $schedule;
    }

    public static function task(string $interval, string $console_command, string $description, bool $prevent_overlapping = true): array
    {
        return [
            'interval'            => $interval,
            'console_command'     => $console_command,
            'description'         => $description,
            'prevent_overlapping' => $prevent_overlapping,
        ];
    }
}
