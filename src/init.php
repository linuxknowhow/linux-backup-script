<?php

if (version_compare(phpversion(), '8.2', '<') == true) {
    echo 'PHP 8.2 or newer is required. Your PHP version is: ' . phpversion() . '. Exiting.' . PHP_EOL;

    exit(1);
}

ignore_user_abort(true);
set_time_limit(0);

require_once __DIR__ . '/../vendor/autoload.php';
