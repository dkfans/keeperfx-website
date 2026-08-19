<?php

namespace App\Helper;

class GitHelper
{
    /**
     * Parse the git log and return all the regex matches with PREG_SET_ORDER set.
     */
    public static function parseCommitsFromGitLog(string $git_log): array|false
    {
        // Regex the result of a 'git log' command
        $result = \preg_match_all("/commit\s([a-f0-9]+)\nAuthor\:\s(.+)\nDate\:\s+(.+)\n\n\s+([.\n\s\S]+?)\n\n(?=diff|commit|$)/", $git_log, $matches, \PREG_SET_ORDER);
        if (!$result) {
            return false;
        }

        // Structure the output
        $return = [];
        foreach ($matches as $match) {

            // Get timestamp
            $timestamp = null;
            if (isset($match[3])) {
                $timestamp = new \DateTime($match[3]);
            }

            // Separate message and note
            $message_raw       = $match[4] ?? '';
            $message_raw       = \preg_replace('/^    /m', '', $message_raw);
            $message_raw       = \preg_replace('/^Co-authored-by\:.*($|\n)/m', '', $message_raw);
            $message_raw_parts = \explode("\n\n", $message_raw);
            if (\count($message_raw_parts) > 1) {
                $message = \array_shift($message_raw_parts);
                $note    = \implode("\n\n", $message_raw_parts);      // Remove indentation
            } else {
                $message = $message_raw;
                $note    = null;
            }

            // Create structure
            $return[] = [
                'hash'      => $match[1] ?? null,
                'author'    => $match[2] ?? null,
                'timestamp' => $timestamp,
                'message'   => $message,
                'note'      => $note,
            ];
        }

        return $return;
    }
}
