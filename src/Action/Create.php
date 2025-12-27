<?php

namespace Backup\Action;

use Backup\Component\Files;
use Backup\Component\MySQL;
use Backup\Component\Cron;
use Backup\Config;
use Backup\Helper\Filesystem;
use Backup\Sequence\ContainersSequence;
use Backup\ContainersProcessor;
use Exception;

class Create {
    private string $name;
    private string $date;

    private string $temp_folder;
    private string $data_folder;

    private Config $config;

    public function __construct(string $name, string $date, string $tmp_folder, Config $config) {
        $this->name = $name;
        $this->date = $date;

        $this->temp_folder = "$tmp_folder/temp";
        $this->data_folder = "$tmp_folder/data";

        $this->config = $config;

        if (Filesystem::createDirectory($this->temp_folder) === false) {
            throw new Exception("Cannot create directory '$this->temp_folder'");
        }

        if (Filesystem::createDirectory($this->data_folder) === false) {
            throw new Exception("Cannot create directory '$this->data_folder'");
        }
    }

    public function do() {
        $local_sources = $this->config->getLocalFolderSources();

        if (!empty($local_sources)) {
            $paths = array_map(fn($source) => $source->getPath(), $local_sources);
            $files = new Files($this->data_folder, $paths);
            $files->create();
        }

        $mysql_sources = $this->config->getMySqlSources();

        if (!empty($mysql_sources)) {
            $mysql_settings = [];
            foreach ($mysql_sources as $source) {
                $mysql_settings[] = [
                    'hostname' => $source->getHostname(),
                    'username' => $source->getUsername(),
                    'password' => $source->getPassword(),
                    'mysql_charset' => $source->getCharset(),
                ];
            }

            $mysql = new MySQL($this->data_folder, $mysql_settings);
            $mysql->create();
        }

        $cron_sources = $this->config->getCronSources();

        if (!empty($cron_sources)) {
            $users = array_map(fn($source) => $source->getUser(), $cron_sources);
            $cron = new Cron($this->data_folder, $users);
            $cron->create();
        }

        $containers_sequence_settings = $this->config->get('containers_sequence');

        if (isset($containers_sequence_settings)) {
            $containers_sequence = new ContainersSequence($containers_sequence_settings);

            $containers_processor = new ContainersProcessor($this->name, $this->date, $this->temp_folder, $this->data_folder, $containers_sequence);

            return $containers_processor->do();

        } else {
            throw new Exception('Containers settings cannot be empty');
        }
    }
}
