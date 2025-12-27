<?php

namespace Backup\Domain\Storage;

class LocalTarget {
    private string $folder;

    public function __construct(string $folder) {
        $this->folder = $folder;
    }

    public function getFolder(): string {
        return $this->folder;
    }
}
