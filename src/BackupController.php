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

        $this->config->validate();

        // TODO: to check for prerequisities

        clearstatcache();

        $this->name = $this->config->get('name');

        $tmp_folder = $this->config->getTmpFolder();

        if ($tmp_folder === null) {
            throw new Exception('The tmp folder is not set in the config');
        }

        if (!is_dir($tmp_folder) || !is_writable($tmp_folder)) {
            mkdir($tmp_folder, 0700);
        }

        if (!is_dir($tmp_folder) || !is_writable($tmp_folder)) {
            throw new Exception('The tmp folder "' . $tmp_folder . '" does not exist or is not writable');
        }

        $tmp_folder_trimmed = trim($tmp_folder, ' /');
        $this->tmp_folder = "/$tmp_folder_trimmed/backup-" . Randomness::getRandomString();

        $this->date = date('Y-m-d');

        $this->assertRequiredBinaries();
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

    private function assertRequiredBinaries(): void {
        $binaries = ['tar', 'gzip']; // Files component uses TarGzip internally

        if (!empty($this->config->getMySqlSources())) {
            $binaries[] = 'mysqldump';
        }

        $containers_sequence_settings = $this->config->get('containers_sequence');

        if (is_array($containers_sequence_settings)) {
            foreach ($containers_sequence_settings as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $key = key($item);

                if (!is_string($key)) {
                    continue;
                }

                $normalized = trim(mb_strtolower($key));

                switch ($normalized) {
                    case 'targz':
                        $binaries[] = 'tar';
                        $binaries[] = 'gzip';
                        break;

                    case '7zip':
                    case '7z':
                        $binaries[] = '7z';
                        break;

                    case 'gpg':
                        $binaries[] = 'gpg';
                        break;

                    case 'rar':
                        $binaries[] = 'rar';
                        break;
                }
            }
        }

        $binaries = array_values(array_unique($binaries));

        foreach ($binaries as $binary) {
            $code = 0;
            exec('command -v ' . escapeshellarg($binary) . ' >/dev/null 2>&1', $output, $code);

            if ($code !== 0) {
                throw new Exception("Required binary '$binary' is not available in PATH");
            }
        }
    }
}
