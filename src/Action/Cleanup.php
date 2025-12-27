<?php

namespace Backup\Action;

use Backup\Config;
use Backup\Domain\Retention;
use Backup\Domain\Storage\LocalTarget;
use Backup\Domain\Storage\S3Target;
use Backup\Storage\AWS\S3;
use Backup\Storage\Local;
use Exception;

class Cleanup {
    private string $name;
    private string $date;

    private Config $config;

    public function __construct(string $name, string $date, Config $config) {
        $this->name = $name;
        $this->date = $date;
        $this->config = $config;
    }

    public function do() {
        $retention_period_years = (int)$this->config->get('retention_periods/years');
        $retention_period_months = (int)$this->config->get('retention_periods/months');
        $retention_period_weeks = (int)$this->config->get('retention_periods/weeks');
        $retention_period_days = (int)$this->config->get('retention_periods/days');

        $retention = new Retention($this->date, $retention_period_years, $retention_period_months, $retention_period_weeks, $retention_period_days);

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
            $backups = $storage->getListOfBackups($this->name);

            if (is_array($backups)) {
                $cleaned_backups = $retention->do($backups);
                $storage->deleteBackups($cleaned_backups);
            }
        }
    }
}
