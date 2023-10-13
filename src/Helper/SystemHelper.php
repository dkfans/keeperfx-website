<?php

namespace App\Helper;

class SystemHelper {

    public static function verifyShellCommand(string $command): bool
    {
        $windows = \strpos(PHP_OS, 'WIN') === 0;
        $test = $windows ? 'where' : 'command -v';
        return \is_executable(\trim(\shell_exec("$test $command")));
    }

}
