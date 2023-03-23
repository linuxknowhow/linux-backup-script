<?php

namespace Backup\Processor;

use Backup\Sequence\Sequence;
use Backup\Helper\Filesystem;

class ContainersProcessor {
    private string $date;

    private string $temp_folder;
    private string $data_folder;

    private Sequence $containers_sequence;

    public function __construct(string $name, string $date, string $temp_folder, string $data_folder, Sequence $containers_sequence) {
        $this->name = $name;
        $this->date = $date;

        $this->temp_folder     = $temp_folder;
        $this->data_folder = $data_folder;

        $this->containers_sequence = $containers_sequence;
    }

    public function do() {
        $source_files = Filesystem::readDir($this->data_folder);

        if ( !count($this->containers_sequence) ) {
            abort("No containers (archivers) were set in the config file!");
        }

        $archive_filename = "{$this->name}-{$this->date}";

        foreach ($this->containers_sequence as $container) {
            $sourse_files_basenames = [];

            foreach ($source_files as $source_file) {
                $sourse_files_basenames[] = basename($source_file);
            }

            $container->setWorkingDirectory($this->data_folder);

            $output_files = $container->create($sourse_files_basenames, $archive_filename, $this->temp_folder);

            foreach ($source_files as $source_file) {
                Filesystem::remove($source_file);
            }

            $source_files = [];

            foreach ($output_files as $output_file) {
                $output_file_basename = basename($output_file);

                $source_file = "{$this->data_folder}/$output_file_basename";

                rename($output_file, $source_file);

                $source_files[] = $source_file;
            }
        }

        Filesystem::remove($this->temp_folder);

        return $source_files;
    }

}
