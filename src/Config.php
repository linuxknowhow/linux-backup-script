<?php

namespace Backup;

use Backup\Domain\Source\CronSource;
use Backup\Domain\Source\LocalFolderSource;
use Backup\Domain\Source\MySqlSource;
use Backup\Domain\Storage\LocalTarget;
use Backup\Domain\Storage\S3Target;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;
use Exception;

class Config {
    private $data = [];

    public function __construct(?string $path = null) {
        if ( empty($path) ) {
            $config_file_path = realpath(__DIR__ . '/../config.yml');
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
                    return null;
                }

            // Nagivating deeper
            } elseif (isset($array[$key]) && is_array($array[$key])) {
                $array = $array[$key];
            } else {
                return null;
            }
        }
    }

    public function validate() {
        $required_fields = [
            'name',
            'advanced_settings/tmp_folder',
            'retention_periods/days'
        ];

        foreach ($required_fields as $required_field) {
            if ($this->get($required_field) === null) {
                throw new Exception('A field in the config is missing or is empty: ' . $required_field);
            }
        }

        if ($this->get('retention_periods/days') < 0) {
            throw new Exception('Param retention_periods/days cannot be 0 or lower.');
        }
    }

    public function getTmpFolder(): ?string {
        $tmp = $this->get('advanced_settings/tmp_folder');

        if ($tmp === null) {
            return null;
        }

        $trimmed = rtrim($tmp, ' /');

        return $trimmed === '' ? null : '/' . ltrim($trimmed, '/');
    }

    /** @return LocalFolderSource[] */
    public function getLocalFolderSources(): array {
        $source_folders = $this->get('sources/local_folders');

        if ($source_folders === null) {
            return [];
        }

        if (is_string($source_folders)) {
            $source_folders = [$source_folders];
        }

        if (!is_array($source_folders)) {
            throw new Exception('Incorrect "sources/local_folders" config value');
        }

        return array_map(fn(string $path) => new LocalFolderSource($path), $source_folders);
    }

    /** @return MySqlSource[] */
    public function getMySqlSources(): array {
        $mysql_list = $this->get('sources/mysql');

        if ($mysql_list === null) {
            return [];
        }

        if (!is_array($mysql_list)) {
            throw new Exception('Incorrect "sources/mysql" config value');
        }

        // Allow single assoc array or list
        if ($this->isAssoc($mysql_list)) {
            $mysql_list = [$mysql_list];
        }

        $sources = [];
        foreach ($mysql_list as $mysql) {
            if (!is_array($mysql)) {
                throw new Exception('Incorrect "sources/mysql" config value');
            }

            $hostname = $mysql['hostname'] ?? null;
            $username = $mysql['username'] ?? null;
            $password = $mysql['password'] ?? null;
            $charset  = $mysql['charset'] ?? 'utf8mb4';

            if ($hostname === null || $username === null) {
                throw new Exception('MySQL source must contain hostname and username');
            }

            $sources[] = new MySqlSource((string)$hostname, (string)$username, $password, (string)$charset);
        }

        return $sources;
    }

    /** @return CronSource[] */
    public function getCronSources(): array {
        $cron_users = $this->get('sources/cron');

        if ($cron_users === null) {
            return [];
        }

        if (is_string($cron_users)) {
            $cron_users = [$cron_users];
        }

        if (!is_array($cron_users)) {
            throw new Exception('Incorrect "sources/cron" config value');
        }

        return array_map(fn(string $user) => new CronSource($user), $cron_users);
    }

    /** @return array<int, LocalTarget|S3Target> */
    public function getStorageTargets(): array {
        $storage_list = $this->get('storage_list');

        if ($storage_list === null) {
            return [];
        }

        if (!is_array($storage_list)) {
            throw new Exception('Storage settings cannot be empty');
        }

        $targets = [];
        foreach ($storage_list as $item) {
            if (!is_array($item)) {
                throw new Exception('Storage settings contain an invalid element');
            }

            $key = key($item);
            $settings = reset($item);

            if (!is_string($key) || !is_array($settings)) {
                throw new Exception('Storage settings contain an invalid element');
            }

            $key = trim(mb_strtolower($key));

            switch ($key) {
                case 'local':
                    $folder = $settings['folder'] ?? null;
                    if ($folder === null) {
                        throw new Exception('Local storage requires "folder"');
                    }
                    $targets[] = new LocalTarget((string)$folder);
                    break;

                case 'aws':
                    $region = $settings['region'] ?? null;
                    $bucket = $settings['bucket'] ?? null;
                    $folder = $settings['folder'] ?? '';
                    $accessKeyId = $settings['access_key_id'] ?? null;
                    $secretKey = $settings['secret_key'] ?? null;

                    if (!$region || !$bucket || !$accessKeyId || !$secretKey) {
                        throw new Exception('AWS storage requires region, bucket, access_key_id and secret_key');
                    }

                    $targets[] = new S3Target($region, $bucket, $folder, $accessKeyId, $secretKey);
                    break;

                default:
                    throw new Exception('Unknown storage provider: ' . $key);
            }
        }

        return $targets;
    }

    private function isAssoc(array $arr): bool {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}
