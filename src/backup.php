<?php

require 'init.php';
require 'functions.php';

use Backup\Config;
use Backup\BackupController;

$config = new Config();

$backup_controller = new BackupController($config);

$options = getopt('', ['create::', 'extract::', 'cleanup::']);

if (empty($options)) {
    echo 'Action is not specified. Please add at least one action:' . PHP_EOL;
    echo '--create  - create and upload backup' . PHP_EOL;
    echo '--extract - extract files from a backup' . PHP_EOL;
    echo '--cleanup - delete expired backups' . PHP_EOL;
}

foreach ($options as $action => $value) {
    switch ($action) {
        case 'create':
            $backup_controller->createAction();
            break;

        case 'extract':
            $backup_controller->extractAction();
            break;

        case 'cleanup':
            $backup_controller->cleanupAction();
            break;
    }
}
