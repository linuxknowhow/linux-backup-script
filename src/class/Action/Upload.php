<?php

namespace Backup\Action;

use Backup\Config;
use Backup\Sequence\StorageSequence;
use Backup\Processor\StorageProcessor;

class Upload {
    private string $name;
    private string $date;

    private array $files;

    private Config $config;

    public function __construct(string $name, string $date, array $files, Config $config) {
        $this->name = $name;
        $this->date = $date;
        $this->files = $files;
        $this->config = $config;
    }

    public function do() {
        $storage_list_settings = $this->config->get('storage_list');

        if (isset($storage_list_settings)) {
            $storage_list = new StorageSequence($storage_list_settings);

            $storage_processor = new StorageProcessor($this->name, $this->date, $this->files, $storage_list);

            $storage_processor->do();
        } else {
            throw new Exception('Storage settings cannot be empty');
        }
    }
}
