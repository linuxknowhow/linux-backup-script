<?php

namespace Backup\Domain\Storage;

use Exception;

class LocalTarget {
    private string $folder;

    public function __construct(string $folder) {
        $normalized = rtrim($folder, " /");

        if ($normalized === '') {
            throw new Exception('Local storage folder cannot be empty');
        }

        $this->folder = $normalized;
    }

    public function getFolder(): string {
        return $this->folder;
    }
}
