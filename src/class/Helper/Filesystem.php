<?php

namespace Backup\Helper;

use Backup\Helper\CommandLine;
use Assert\Assert;
use Assert\LazyAssertionException;

class Filesystem {
    public static function createDirectory($path) {
        $escaped_path = escapeshellarg($path);

        $result = (bool)exec("mkdir -p $escaped_path 2>&1");

        $exists = file_exists($path);
        $is_dir = is_dir($path);
        $is_readable = is_readable($path);
        $is_writable = is_writable($path);

        return $result & $exists & $is_dir & $is_readable & $is_writable;
    }

    public static function readDir($path) {
        if (!is_dir($path)) {
            abort('"' . $path . '" is not a directory');
        }

        $results = [];

        $files = array_diff(scandir($path), ['..', '.']);

        foreach ($files as $file) {
            $results[] = "$path/$file";
        }

        return $results;
    }

    public static function remove($path) {
        try {
            Assert::lazy()->tryAll()
                ->that($path)
                    ->string()
                    ->notEmpty()
                    ->notSame('/')
                    ->notSame('.')
                    ->notSame('..')
                    ->notContains('*')
                    ->notContains('?')
                    ->betweenLength(1, 4096)
                ->verifyNow();
        } catch (LazyAssertionException $e) {
            abort($e->getMessage());
        } catch (\Throwable $e) {
            abort("Fatal error: " . $e->getMessage());
        }

        $command = "rm -rf " . escapeshellarg($path);

        CommandLine::exec($command);
    }

    public static function isAbsolutePath(string $path): bool {
        return mb_substr($path, 0, 1) === '/' ? true : false;
    }

    public static function assertFileMimetype(string $filepath, string $mimetype) {
        $file_mime_type = mime_content_type($filepath);

        if ($file_mime_type === $mimetype) {
            return true;
        } else {
            return false;
        }
    }
}
