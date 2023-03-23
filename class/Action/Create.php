<?php

namespace Backup\Action;

use Backup\Component\Files;
use Backup\Component\MySQL;
use Backup\Component\Cron;
use Backup\Config;
use Backup\Helper\Filesystem;
use Backup\Sequence\ContainersSequence;
use Backup\Processor\ContainersProcessor;

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

        if ( Filesystem::createDirectory($this->temp_folder) === false ) {
            abort("Cannot create directory '$this->temp_folder'");
        }

        if ( Filesystem::createDirectory($this->data_folder) === false ) {
            abort("Cannot create directory '$this->data_folder'");
        }
    }

    public function do() {
        $source_folders = $this->config->get('sources/local_folders');

        if ( isset( $source_folders ) ) {
            if ( is_string($source_folders) ) {
                $files = new Files( $this->data_folder, [$source_folders] );

            } elseif ( is_array($source_folders) ) {
                $files = new Files($this->data_folder, $source_folders);

            } else {
                abort('Incorrect "sources/local_folders" config value');
            }

            $files->create();
        }

        $mysql_list = $this->config->get('sources/mysql');

        if ( isset( $mysql_list ) ) {
            if ( is_array($mysql_list) ) {
                $first_element = reset($mysql_list);

                if ( key_exists('hostname', $mysql_list) && !empty( $mysql_list['hostname'] ) && is_string( $mysql_list['hostname'] ) ) {
                    $mysql = new MySQL( $this->data_folder, [$mysql_list] );

                } elseif ( is_array($first_element) ) {
                    $mysql = new MySQL($this->data_folder, $mysql_list);

                } else {
                    abort('Incorrect "sources/mysql" config value');
                }

            } else {
                abort('Incorrect "sources/mysql" config value');
            }

            $mysql->create();
        }

        $cron_users = $this->config->get('sources/cron');

        if ( isset( $cron_users ) ) {
            if ( is_string($cron_users) ) {
                $cron = new Cron( $this->data_folder, [$cron_users] );

            } elseif ( is_array($cron_users) ) {
                $cron = new Cron($this->data_folder, $cron_users);

            } else {
                abort('Incorrect "sources/cron" config value');
            }

            $cron->create();
        }

        $containers_settings = $this->config->get('containers');

        if ( isset( $containers_settings ) ) {
            $containers_sequence = new ContainersSequence($containers_settings);

            $containers_processor = new ContainersProcessor( $this->name, $this->date, $this->temp_folder, $this->data_folder, $containers_sequence );

            return $containers_processor->do();

        } else {
            abort('Containers settings cannot be empty');
        }
    }

}
