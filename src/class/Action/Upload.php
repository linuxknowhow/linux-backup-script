<?php

namespace Backup\Action;

use Backup\Config;
use Backup\Sequence\StorageSequence;
use Exception;

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

        if ( isset($storage_list_settings) ) {
            $storage_list = new StorageSequence($storage_list_settings);

            if ( !count($storage_list) ) {
                throw new Exception("No storage destination (\"where to upload backups\") were set in the config file!");
            }

            foreach ($storage_list as $storage) {
                foreach ($this->files as $file) {
                    // TODO: To check if $file exists

                    echo 'Uploading file: ' . $file . PHP_EOL;

                    $storage->addFile($file);
                }
            }

        } else {
            throw new Exception('Storage settings cannot be empty');
        }
    }
}
