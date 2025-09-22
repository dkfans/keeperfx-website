<?php

namespace App\Helper;

class SystemHelper
{

    /**
     * Check if a shell command is valid.
     *
     * This is an **UNSAFE** function and should never process user input.
     * This function is useful for checking if we have access to a command line tool.
     *
     * @param string $command
     * @return boolean
     */
    public static function verifyShellCommand(string $command): bool
    {
        $windows = \strpos(PHP_OS, 'WIN') === 0;
        $test = $windows ? 'where' : 'command -v';
        return \is_executable(\trim(\shell_exec("$test $command")));
    }
}
