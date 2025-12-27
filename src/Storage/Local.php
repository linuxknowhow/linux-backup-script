<?php

namespace Backup\Storage;

use Backup\Domain\Backup;
use Backup\Storage\CommonInterface;
use Backup\Helper\Filesystem;
use Assert\Assert;
use Assert\LazyAssertionException;
use Exception;

class Local implements CommonInterface {
    private $destionation_folder;

    public function __construct(array $settings) {
        foreach ($settings as $setting_key => $setting_value) {
            switch ($setting_key) {
                case 'folder':
                    $destionation_folder_trimmed = trim($setting_value, ' /');

                    $this->destionation_folder = "/$destionation_folder_trimmed/";

                    if (Filesystem::createDirectory($this->destionation_folder) === false) {
                        throw new Exception("Cannot create directory '$this->destionation_folder'");
                    }

                    break;

                default:
                    throw new Exception("Invalid setting in the Local settings section of the storage providers found: '" . $setting_key . "'");
            }
        }
    }

    public function getListOfBackups(string $backup_name) {
        $files = array_diff(scandir($this->destionation_folder), ['..', '.']);

        $backups = [];

        $backup_name_length = mb_strlen($backup_name);

        foreach ($files as $filename) {
            $fullpath = $this->destionation_folder . $filename;

            $pos = strpos($filename, $backup_name);

            if ( $pos === 0 ) {
                $filename_part_potentially_with_date = substr($filename, $backup_name_length+1);

                if ( !empty($filename_part_potentially_with_date) && is_string($filename_part_potentially_with_date) ) {
                    if (preg_match_all('/^([0-9]{4})-([0-9]{2})-([0-9]{2})/u', $filename_part_potentially_with_date, $matches, PREG_PATTERN_ORDER)) {
                        if ( !is_dir($fullpath) ) {
                            $backup = new Backup($filename, $fullpath, (int)$matches[1][0], (int)$matches[2][0], (int)$matches[3][0]);

                            $backups[] = $backup;
                        }
                    }
                }
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
            throw new Exception($e->getMessage());
        } catch (\Throwable $e) {
            throw new Exception("Fatal error: " . $e->getMessage());
        }

        if ( file_exists($this->destionation_folder) && is_dir($this->destionation_folder) && is_writable($this->destionation_folder) ) {
            $filename = basename($filepath);

            if ( !copy($filepath, $this->destionation_folder . $filename) ) {
                throw new Exception("Cannot save backup to local storage: couldn't copy the backup file into destination folder");
            }
        } else {
            throw new Exception("Cannot save backup to local storage: destination folder cannot be accessed");
        }
    }

    public function deleteBackups(array $backups) {
        foreach ($backups as $backup) {
            if (false === $backup->isPreserved() && !empty($backup->getFullpath())) {
                Filesystem::remove( $backup->getFullpath() );
            }
        }
    }
}
