<?php

namespace Backup;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;
use Exception;

class Config {
    private $data = [];

    public function __construct(?string $path = null) {
        if ( empty($path) ) {
            $config_file_path = realpath(__DIR__ . '/../../config.yml');
        } else {
            $config_file_path = $path;
        }

        if (!file_exists($config_file_path)) {
            throw new Exception("Config file was not found at the following location: '$config_file_path'");
        }

        if (!is_readable($config_file_path)) {
            throw new Exception("The config file is not readable. Please fix access rights for the file: '$config_file_path'");
        }

        try {
            $this->data = Yaml::parse(file_get_contents($config_file_path));
        } catch (ParseException $exception) {
            throw new Exception(printf('Unable to parse the YAML string: %s', $exception->getMessage()));
        }
    }

    public function get(string $request_string) {
        $keys = explode('/', $request_string);

        $count = count($keys);

        $array = $this->data;

        $i = 0;

        foreach ($keys as $key) {
            $i++;

            // Key found
            if ($i == $count) {
                if (isset($array[$key])) {
                    return $array[$key];
                } else {
                    throw new Exception("Incorrect config key: '$request_string'");
                }
            // Nagivating deeper
            } elseif (isset($array[$key]) && is_array($array[$key])) {
                $array = $array[$key];
            } else {
                throw new Exception("Incorrect config key: '$request_string'");
            }
        }
    }

    public function validate() {
        $required_fields = [
            'name',
            'tmp_folder',
            'retention_periods/days'
        ];

        foreach ($required_fields as $required_field) {
            if (false === $this->get($required_field)) {
                throw new Exception('A field in the config is missing or is empty: ' . $required_field);
            }
        }

        $a = $this->get('retention_periods/days');

        if ((false === $this->get('retention_periods/days')) || $this->get('retention_periods/days') < 0) {
            throw new Exception('Param retention_periods/days cannot be 0 or lower.');
        }
    }
}
