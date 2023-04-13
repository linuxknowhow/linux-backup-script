<?php

namespace Backup\Component;

use Backup\Container\TarGzip;
use Backup\Helper\Filesystem;

class Files {
    private array $source_folders;

    private string $folder;

    public function __construct(string $data_folder, array $source_folders) {
        $this->source_folders = $source_folders;

        $this->folder = "$data_folder/files";

        if (Filesystem::createDirectory($this->folder) === false) {
            throw new Exception("Cannot create directory '{$this->folder}'");
        }
    }

    public function create() {
        if (isset($this->source_folders) && is_array($this->source_folders)) {
            foreach ($this->source_folders as $source_folder_fullpath) {
                if (file_exists($source_folder_fullpath) && is_dir($source_folder_fullpath) && is_readable($source_folder_fullpath)) {
                    $archive_name_cleaned = preg_replace("/[^A-Za-z0-9]+/", '_', $source_folder_fullpath);
                    $archive_name = trim($archive_name_cleaned, '_');

                    // TODO: To fix: What if the names collide?

                    if (!empty($archive_name)) {
                        $archiver = new TarGzip();

                        $archiver->create([$source_folder_fullpath], $archive_name, $this->folder);
                    }
                } else {
                    throw new Exception("Directory '$source_folder_fullpath' doesn't exist or cannot be accessed");
                }
            }
        }
    }
}
