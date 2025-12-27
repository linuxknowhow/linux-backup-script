<?php

namespace Backup\Action;

use Backup\Config;
use Backup\Sequence\StorageSequence;
use Backup\Domain\Retention;
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

        $storage_list_settings = $this->config->get('storage_list');

        if ( isset($storage_list_settings) ) {
            $storage_list = new StorageSequence($storage_list_settings);

            if ( !count($storage_list) ) {
                throw new Exception("No storage destination (\"where to upload backups\") were set in the config file!");
            }

            foreach ($storage_list as $storage) {
                $backups = $storage->getListOfBackups($this->name);

                if ( is_array($backups) ) {
                    $cleaned_backups = $retention->do($backups);

                    $storage->deleteBackups($cleaned_backups);
                }
            }

        } else {
            throw new Exception('Storage settings cannot be empty');
        }
    }
}
