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
        // Regex the result of a 'git log' command
        $result = \preg_match_all("/commit\s([a-f0-9]+)\nAuthor\:\s(.+)\nDate\:\s+(.+)\n\n\s+(.+)/", $git_log, $matches, \PREG_SET_ORDER);
        if(!$result){
            return false;
        }

        // Structure the output
        $return = [];
        foreach($matches as $match)
        {

            if(!isset($match[2])){

            var_dump($match);
            die();
            }

            // Get timestamp
            $timestamp = null;
            if(isset($match[3])){
                $timestamp = new \DateTime($match[3]);
            }

            // Create structure
            $return[] = [
                'hash'      => $match[1] ?? null,
                'author'    => $match[2] ?? null,
                'message'   => $match[4] ?? null,
                'timestamp' => $timestamp,
            ];
        }

        return $return;
    }
}
