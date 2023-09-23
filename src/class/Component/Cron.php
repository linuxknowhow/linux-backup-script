<?php

namespace Backup\Component;

use Backup\Helper\Filesystem;
use Backup\Helper\CommandLine;

class Cron {
    private array $cron_users;

    private string $folder;

    public function __construct(string $data_folder, array $cron_users) {
        $this->cron_users = $cron_users;

        $this->folder = "$data_folder/cron";

        if (Filesystem::createDirectory($this->folder) === false) {
            throw new Exception("Cannot create directory '{$this->folder}'");
        }
    }

    public function create() {
        foreach ($this->cron_users as $cron_user) {
            // TODO: To validate

            $cron_user_escaped = escapeshellarg($cron_user);

            $command = "crontab -u $cron_user_escaped -l";

            $result = CommandLine::exec($command, null, $output);

            if (!$result) {
                throw new Exception("Cannot obtain the list of cron jobs for the following user: '$cron_user_escaped'");
            }

            file_put_contents("{$this->folder}/$cron_user", $output);
        }
    }
}
