<?php

if ( version_compare( phpversion(), '7.4', '<' ) == true ) {
    echo 'PHP 7.4 or newer is required. Your PHP version is: ' . phpversion() . '. Exiting.' . PHP_EOL;

    exit(1);
}

ignore_user_abort(true);
set_time_limit(0);

require_once __DIR__ . '/vendor/autoload.php';