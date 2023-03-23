<?php

namespace Backup\Processor;

use Backup\Sequence\Sequence;
use Backup\Helper\Filesystem;

class StorageProcessor {
    private string $name;
    private string $date;

    private array $files;

    private Sequence $storage_sequence;

    public function __construct(string $name, string $date, array $files, Sequence $storage_sequence) {
        $this->name = $name;
        $this->date = $date;

        $this->files = $files;

        $this->storage_sequence = $storage_sequence;
    }

    public function do() {
        if ( !count($this->storage_sequence) ) {
            abort("No storage destination (\"where to upload backups\") were set in the config file!");
        }

        foreach ($this->storage_sequence as $storage) {
            foreach ($this->files as $file) {
                $storage->addFile($file);

                echo 'Uploading file: ' . $file . PHP_EOL;
            }
        }
    }

}
