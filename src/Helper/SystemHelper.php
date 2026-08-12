<?php

namespace App\Helper;

class SystemHelper
{
    /**
     * Check if a shell command is valid.
     *
     * This is an **UNSAFE** function and should never process user input.
     * This function is useful for checking if we have access to a command line tool.
     */
    public static function verifyShellCommand(string $command): bool
    {
        $windows = \str_starts_with(\PHP_OS, 'WIN');
        $test    = $windows ? 'where' : 'command -v';

        return \is_executable(\trim(\shell_exec("$test $command")));
    }
}
