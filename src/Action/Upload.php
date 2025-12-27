<?php

namespace Backup\Action;

use Backup\Config;
use Backup\Domain\Storage\LocalTarget;
use Backup\Domain\Storage\S3Target;
use Backup\Storage\AWS\S3;
use Backup\Storage\Local;
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
        $targets = $this->config->getStorageTargets();

        if (empty($targets)) {
            throw new Exception('Storage settings cannot be empty');
        }

        $storages = [];

        foreach ($targets as $target) {
            if ($target instanceof LocalTarget) {
                $storages[] = new Local(['folder' => $target->getFolder()]);
            } elseif ($target instanceof S3Target) {
                $storages[] = new S3([
                    'region' => $target->getRegion(),
                    'bucket' => $target->getBucket(),
                    'folder' => $target->getFolder(),
                    'access_key_id' => $target->getAccessKeyId(),
                    'secret_key' => $target->getSecretKey(),
                ]);
            } else {
                throw new Exception('Unsupported storage target');
            }
        }

        foreach ($storages as $storage) {
            foreach ($this->files as $file) {
                // TODO: To check if $file exists
                echo 'Uploading file: ' . $file . PHP_EOL;
                $storage->addFile($file);
            }
        }
    }
}
