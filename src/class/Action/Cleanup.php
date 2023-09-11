<?php

namespace Backup\Action;

use Backup\Config;
use Backup\Sequence\StorageSequence;

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

        $cleanUpModel = new CleanUp($this->date, $retention_period_years, $retention_period_months, $retention_period_weeks, $retention_period_days);

        foreach ($this->storage_list as $storage) {
            $backups = $storage->getListOfBackups();

            if ( is_array($backups) ) {
                $cleaned_backups = $cleanUpModel->do($backups);

                // $storage->deleteBackups($cleaned_backups);
            }
        }
    }
}
