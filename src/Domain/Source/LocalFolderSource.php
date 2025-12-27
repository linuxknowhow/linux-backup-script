<?php

namespace Backup\Domain\Source;

use Exception;

class LocalFolderSource {
    private string $path;

    public function __construct(string $path) {
        $normalized = rtrim($path, " ");

        if ($normalized === '') {
            throw new Exception('Local folder source path cannot be empty');
        }

        $this->path = $normalized;
    }

    public function getPath(): string {
        return $this->path;
    }
}
