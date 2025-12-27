<?php

namespace Backup\Domain\Source;

class LocalFolderSource {
    private string $path;

    public function __construct(string $path) {
        $this->path = $path;
    }

    public function getPath(): string {
        return $this->path;
    }
}
