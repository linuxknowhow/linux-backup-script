<?php

namespace Backup\Storage;

use Backup\Entity;
use Backup\Storage\CommonInterface;
use Backup\Helper\Filesystem;
use Assert\Assert;
use Assert\LazyAssertionException;

class Local implements CommonInterface {
    private $destionation_folder;

    public function __construct(array $settings) {
        foreach ($settings as $setting_key => $setting_value) {
            switch ($setting_key) {
                case 'folder':
                    $destionation_folder_trimmed = trim($setting_value, ' /');

                    $this->destionation_folder = "/$destionation_folder_trimmed/";

                    if (Filesystem::createDirectory($this->destionation_folder) === false) {
                        abort("Cannot create directory '$this->destionation_folder'");
                    }

                    break;

                default:
                    abort("Invalid setting in the Local settings section of the storage providers found: '" . $setting_key . "'");
            }
        }
    }

    public function getListOfBackups() {
        $backup_files = array_diff(scandir($this->destionation_folder), ['..', '.']);

        $backups = [];

        foreach ($backup_files as $backup_file_name) {
            $backup_date = str_replace(['.7z', 'tar.gz', 'tgz' ], '', $backup_file_name);

            $fullpath = $this->destionation_folder . $backup_file_name;

            if (preg_match_all('/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/u', $backup_date, $matches, PREG_PATTERN_ORDER)) {
                $backup = new Backup($backup_file_name, $fullpath, (int)$matches[1][0], (int)$matches[2][0], (int)$matches[3][0]);

                $backups[] = $backup;
            }
        }

        return $backups;
    }

    public function addFile(string $filepath) {
        try {
            Assert::lazy()->tryAll()
                ->that($filepath)
                    ->notEmpty("Cannot add a file with an empty path")
                    ->string()
                ->verifyNow();
        } catch (LazyAssertionException $e) {
            abort($e->getMessage());
        } catch (\Throwable $e) {
            abort("Fatal error: " . $e->getMessage());
        }

        if (file_exists($this->destionation_folder) && is_dir($this->destionation_folder) && is_writable($this->destionation_folder)) {
            $filename = basename($filepath);

            if (!copy($filepath, $this->destionation_folder . $filename)) {
                abort("Cannot save backup to local storage: couldn't copy the backup file into destination folder");
            }
        } else {
            abort("Cannot save backup to local storage: destination folder cannot be accessed");
        }
    }

    public function cleanupBackups(array $backups) {
        foreach ($backups as $backup) {
            if (false === $backup->isPreserved() && !empty($backup->getFilename())) {
                // TODO:
                // Filesystem::remove( $backup->getFullpath() );
            }
        }
    }
}
