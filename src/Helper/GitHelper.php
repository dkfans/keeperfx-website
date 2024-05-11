<?php

namespace App\Helper;

class GitHelper {

    /**
     * Parse the git log and return all the regex matches with PREG_SET_ORDER set.
     *
     * @param string $git_log
     * @return array|false
     */
    public static function parseCommitsFromGitLog(string $git_log): array|false
    {
        $result = \preg_match_all("/commit\s([a-f0-9]+)\nAuthor\:\s(.+)\nDate\:\s+(.+)\n\n\s+(.+)/", $git_log, $matches, \PREG_SET_ORDER);

        if(!$result){
            return false;
        }

        return $matches;
    }
}
