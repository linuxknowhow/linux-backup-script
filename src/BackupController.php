<?php

namespace Backup;

use Backup\Action\Create;
use Backup\Action\Upload;
use Backup\Action\Cleanup;
use Backup\Config;
use Backup\Helper\Randomness;
use Backup\Sequence\ContainersSequence;
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

        $tmp_folder = $this->config->get('advanced_settings/tmp_folder');

        if (!is_dir($tmp_folder) || !is_writable($tmp_folder)) {
            mkdir($tmp_folder, 0700);
        }

        if (!is_dir($tmp_folder) || !is_writable($tmp_folder)) {
            throw new Exception('The tmp folder "' . $tmp_folder . '" does not exist or is not writable');
        }

        $tmp_folder_trimmed = trim($tmp_folder, ' /');
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
    }

    public function __destruct() {
        Filesystem::remove($this->tmp_folder);
    }
}
