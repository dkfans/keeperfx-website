<?php

namespace App\Controller\AdminCP;

use App\Helper\TailHelper;
use Cron\CronExpression;
use Crunz\Event;
use Crunz\Schedule;
use Lorisleiva\CronTranslator\CronTranslator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;
use Symfony\Component\Lock\Lock;
use Twig\Environment as TwigEnvironment;

class AdminBackgroundTaskController
{
    private const TASKS_DIR = APP_ROOT . '/tasks';

    private function getSchedulesAndTasks(): array
    {
        $schedules = [];

        /** @var \DirectoryIterator $file */
        foreach (new \DirectoryIterator(self::TASKS_DIR) as $file) {

            if ($file->isDot()) {
                continue;
            }

            $filename = $file->getFilename();
            $filepath = $file->getRealPath();
            $basename = \basename($filename, 'Tasks.php');
            $name     = \trim(\implode(' ', \preg_split('/(?=[A-Z])/', $basename)));

            /** @var Schedule $schedule */
            $schedule = require $filepath;
            $events   = $schedule->events();

            $tasks = [];

            if (\count($events) > 0) {
                /** @var Event $event */
                foreach ($events as $index => $event) {

                    $cron_expression = $event->getExpression();
                    $cron            = new CronExpression($cron_expression);

                    $tasks[$index] = [
                        'summary'        => $event->getSummaryForDisplay(),
                        'expression'     => CronTranslator::translate($cron_expression, 'en', true),
                        'expression_raw' => $cron_expression,
                        'next_run'       => $cron->getNextRunDate(),
                        'previous_run'   => $cron->getPreviousRunDate(),
                        'task_id'        => "{$basename}:{$index}",
                        'output'         => $event->output,
                        'output_exists'  => ($event->output !== '/dev/null') ? \file_exists($event->output) : false,
                        'event'          => $event,
                    ];
                }
            }

            $schedules[$basename] = [
                'filename' => $filename,
                'filepath' => $filepath,
                'basename' => $basename,
                'name'     => $name,
                'tasks'    => $tasks,
            ];
        }

        return $schedules;
    }

    public function index(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
    ): Response {

        $response->getBody()->write(
            $twig->render('admincp/tasks.admincp.html.twig', [
                'schedules' => $this->getSchedulesAndTasks(),
            ])
        );

        return $response;
    }

    public function run(
        Request $request,
        Response $response,
        string $task_id,
    ): Response {

        // Output JSON
        $response = $response->withHeader('Content-Type', 'application/json');

        // Find schedules
        $schedules = $this->getSchedulesAndTasks();
        if (\count($schedules) === 0) {
            $response->getBody()->write(
                \json_encode([
                    'success' => false,
                    'error'   => 'NO_SCHEDULES_FOUND',
                ])
            );

            return $response;
        }

        // Loop trough all schedules
        foreach ($schedules as $schedule) {

            // Make sure this schedule has tasks
            if (\count($schedule['tasks']) < 1) {
                continue;
            }

            // Loop trough all tasks
            foreach ($schedule['tasks'] as $task) {

                // Make sure the given run id matches that of this task
                if ($task_id !== $task['task_id']) {
                    continue;
                }

                // Get the command
                /** @var Event $event */
                $event   = $task['event'];
                $command = $event->buildCommand();

                // Replace php binary with the system wide one
                $command_parts = \explode(' ', $command);
                \array_shift($command_parts);
                $command_parts = ['php', ...$command_parts];
                $command       = \implode(' ', $command_parts);

                // Increase execution time at this point because we're going to run it from the browser
                \set_time_limit(60 * 10); // 10 minutes

                // Lock the execution of this task
                $method = new \ReflectionMethod($event, 'createLockObject');
                /** @var Lock $lock */
                $lock = $method->invoke($event);

                // Execute!
                $result = \shell_exec($command);

                // Release the lock
                $lock->release();

                // Make sure result was successful
                if (empty($result)) {
                    $response->getBody()->write(
                        \json_encode([
                            'success' => false,
                            'error'   => 'FAILED_TO_RUN_COMMAND',
                        ])
                    );

                    return $response;
                }

                // Append to log file
                if ($task['output'] !== '/dev/null') {
                    $log_contents = \file_exists($task['output']) ? "\n\n" : '';
                    $log_contents .= '> ' . $command . "\n";
                    $log_contents .= $result;
                    \file_put_contents($task['output'], $log_contents, \FILE_APPEND | \LOCK_EX);
                }

                // Write response
                $response->getBody()->write(
                    \json_encode([
                        'success' => true,
                        'task_id' => $task_id,
                        'command' => $command,
                        'result'  => $result,
                    ])
                );

                return $response;
            }

        }

        $response->getBody()->write(
            \json_encode([
                'success' => false,
                'error'   => 'TASK_NOT_FOUND',
            ])
        );

        return $response;
    }

    public function log(
        Request $request,
        Response $response,
        string $task_id,
    ): Response {

        $response = $response->withHeader('Content-Type', 'text/plain');

        // Find schedules
        $schedules = $this->getSchedulesAndTasks();
        if (\count($schedules) === 0) {
            throw new HttpNotFoundException($request);
        }

        // Loop trough all schedules
        foreach ($schedules as $schedule) {

            // Make sure this schedule has tasks
            if (\count($schedule['tasks']) < 1) {
                continue;
            }

            // Loop trough all tasks
            foreach ($schedule['tasks'] as $task) {

                // Make sure the given run id matches that of this task
                if ($task_id !== $task['task_id']) {
                    continue;
                }

                if ($task['output'] == '/dev/null') {
                    continue;
                }

                $response->getBody()->write(
                    TailHelper::tail(
                        filepath: $task['output'],
                        lines: 200,
                        adaptive: true,
                        lock: true,
                    )
                );

                return $response;
            }
        }

        $response->getBody()->write('LOG NOT FOUND OR EMPTY');

        return $response;
    }
}
