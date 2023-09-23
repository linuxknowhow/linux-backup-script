<?php

namespace Backup\Helper;

use Assert\Assert;
use Assert\LazyAssertionException;
use Exception;

class CommandLine {
    public static function exec(string $command, ?string $cwd = null, string &$output = null): bool {
        $descriptorspec = [
            0 => ["file", "/dev/null", "r"],    // stdin is a pipe that the child will read from
            1 => ["pipe", "w"],                 // stdout is a pipe that the child will write to
            2 => ["file", "/dev/null", "w"]     // stderr is a pipe that the child will write to
        ];

        // TODO: To remove later and to log instead
        echo $command . PHP_EOL;

        // TODO: To put priority into config
        $process = proc_open("nice -n 5 $command", $descriptorspec, $pipes, $cwd);

        if (is_resource($process)) {
            // $pipes now looks like this:
            // 1 => readable handle connected to child stdout
            // Any error output will be disregarded

            $output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            // It is important that you close any pipes before calling
            // proc_close in order to avoid a deadlock
            $result = proc_close($process);

            return !$result;
        } else {
            throw new Exception("Cannot open a process in shell");
        }
    }
}
