<?php

namespace Backup;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

class Config {
    private $data = [];

    public function __construct() {
        $config_file_path = realpath(__DIR__ . '/../../config.yml');

        if (!file_exists($config_file_path)) {
            abort("Config file was not found at the following location: '" . realpath(__DIR__ . '/..') . '/config.yml' . "'");
        }

        if (!is_readable($config_file_path)) {
            abort("The config file is not readable. Please fix access rights for the file: '" . $config_file_path . "'");
        }

        try {
            $this->data = Yaml::parse(file_get_contents($config_file_path));
        } catch (ParseException $exception) {
            abort(printf('Unable to parse the YAML string: %s', $exception->getMessage()));
        }
    }

    public function get(string $request_string) {
        $keys = explode('/', $request_string);

        $count = count($keys);

        $array = $this->data;

        $i = 0;

        foreach ($keys as $key) {
            $i++;

            if ($i == $count) {
                return $array[$key] ?? null;
            }

            if (isset($array[$key]) && is_array($array[$key])) {
                $array = $array[$key];
            } else {
                abort("Incorrect config key request: '$request_string'");
            }
        }
    }

    public function validate() {
        $required_fields = [
            'tmp_folder',
            'retention_periods/days'
        ];

        foreach ($required_fields as $required_field) {
            if (false === $this->get($required_field)) {
                abort('A field in the config is missing or is empty: ' . $required_field);
            }
        }

        if ((false === $this->get('retention/retention_period_days')) || $this->get('retention/retention_period_days') < 0) {
            abort('Param retention_period_days cannot be 0 or lower.');
        }
    }
}
