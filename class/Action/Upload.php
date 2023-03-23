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
        $storage_settings = $this->config->get('storage');

        if ( isset( $storage_settings ) ) {
            $storage_sequence = new StorageSequence($storage_settings);

            $storage_processor = new StorageProcessor( $this->name, $this->date, $this->files, $storage_sequence );

            $storage_processor->do();

        } else {
            abort('Storage settings cannot be empty');
        }
    }

}
