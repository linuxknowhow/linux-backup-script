<?php

namespace Backup;

use Backup\Action\BackupDatabases;
use Backup\Action\BackupFiles;
use Backup\Action\Create;
use Backup\Action\Upload;
use Backup\Action\Cleanup;
use Backup\Sequence\Sequence;
use Backup\Config;
use Backup\Helper\Randomness;
use Backup\StorageList;
use Backup\Sequence\ContainersSequence;
use Backup\Sequence\StorageSequence;
use Backup\Helper\Filesystem;
use Exception;

class BackupController {
    private Config $config;

    private $name;
    private $date;

    private $tmp_folder;
    private $files;

    public function __construct($config) {
        $this->config = $config;

        // TODO: to check for prerequisities

        clearstatcache();

        $this->name = $this->config->get('name');

        if (!is_dir($this->config->get('tmp_folder')) || !is_writable($this->config->get('tmp_folder'))) {
            throw new Exception('The tmp folder "' . $this->config->get('tmp_folder') . '" does not exist or is not writable');
        }

        $tmp_folder_trimmed = trim($this->config->get('tmp_folder'), ' /');
        $this->tmp_folder = "/$tmp_folder_trimmed/backup-" . Randomness::getRandomString();

        $this->date = date('Y-m-d');
    }

    public function createAction() {
        $this->create();
        $this->upload();
    }

    public function cleanupAction() {
        $this->cleanUp();
    }

    private function create() {
        $action = new Create($this->name, $this->date, $this->tmp_folder, $this->config);

        $this->files = $action->do();
    }

    private function upload() {
        $action = new Upload($this->name, $this->date, $this->files, $this->config);

        $action->do();
    }

    private function cleanUp() {
        $action = new Cleanup($this->name, $this->date, $this->config);

        $action->do();
    }

    public function extractAction() {
        global $argv;

        $options = getopt('', ["openssl_password::", "7z_password::"]);

        $custom_openssl_password = $options['openssl_password'] ?? false;
        $custom_sevenzip_password = $options['7z_password'] ?? false;

        if (!empty($custom_openssl_password)) {
            $this->passwords_list['gzip_openssl'] = $custom_openssl_password;
        }

        if (!empty($custom_sevenzip_password)) {
            $this->passwords_list['7zip'] = $custom_sevenzip_password;
        }

        $rest_index = null;
        $opts = getopt('a:b:', [], $rest_index);
        $pos_args = array_slice($argv, $rest_index);

        $backup_filepath = $pos_args[0] ?? false;
        $extraction_folder = $pos_args[1] ?? false;

        if (empty($extraction_folder)) {
            $extraction_folder = '.';
        }

        $extraction_folder_realpath = realpath($extraction_folder);
    }

    public function __destruct() {
        Filesystem::remove($this->tmp_folder);
    }
}
