<?php

namespace Backup\Component;

use Backup\Helper\Filesystem;
use Backup\Helper\CommandLine;
use Exception;

class MySQL {
    private array $mysql_login_data_list;

    private string $folder;

    public function __construct(string $data_folder, array $mysql_login_data_list) {
        $this->mysql_login_data_list = $mysql_login_data_list;

        $this->folder = "$data_folder/mysql";

        if (Filesystem::createDirectory($this->folder) === false) {
            throw new Exception("Cannot create directory '{$this->folder}'");
        }
    }

    public function create() {
        $count = count($this->mysql_login_data_list);

        $i = 1;

        foreach ($this->mysql_login_data_list as $mysql_login_data) {
            if ($count == 1) {
                $output_folder = $this->folder;
            } else {
                $output_folder = "{$this->folder}/$i-{$mysql_login_data['hostname']}";

                if (Filesystem::createDirectory($output_folder) === false) {
                    throw new Exception("Cannot create directory '$output_folder'");
                }
            }

            $link = mysqli_connect($mysql_login_data['hostname'], $mysql_login_data['username'], $mysql_login_data['password']);

            if (!$link) {
                throw new Exception("MySQL->__construct(): Could not make a MySQL database link using '{$mysql_login_data['username']}@{$mysql_login_data['hostname']}'");
            }

            if (mysqli_connect_error()) {
                throw new Exception('MySQL->__construct(): MySQL connection failed: (' . mysqli_connect_errno() . ') '. mysqli_connect_error());
            }

            if (isset($mysql_login_data['mysql_charset']) && !empty($mysql_login_data['mysql_charset'])) {
                mysqli_query($link, "SET NAMES '{$mysql_login_data['mysql_charset']}'");
                mysqli_query($link, "SET CHARACTER SET {$mysql_login_data['mysql_charset']}");
                mysqli_query($link, "SET CHARACTER_SET_CONNECTION={$mysql_login_data['mysql_charset']}");
            }

            if (isset($mysql_login_data['databases'])) {
                if (is_string($mysql_login_data['databases'])) {
                    $mysql_databases = [ $mysql_login_data['databases'] ];
                } else {
                    $mysql_databases = $mysql_login_data['databases'];
                }
            }

            if (isset($mysql_databases)) {
                $where = "`Database` LIKE '" . implode("' OR `Database` LIKE '", $mysql_databases) . "'";

                $result = mysqli_query($link, "SHOW DATABASES WHERE $where");
            } else {
                $result = mysqli_query($link, "SHOW DATABASES");
            }

            $skip_databases = ['performance_schema'];

            while ($row = mysqli_fetch_array($result)) {
                $database_to_backup = $row[0];

                if (in_array($database_to_backup, $skip_databases)) {
                    continue;
                }

                $file_path = "$output_folder/$database_to_backup.sql";

                $command = "mysqldump -u{$mysql_login_data['username']} -p{$mysql_login_data['password']} --host={$mysql_login_data['hostname']} --skip-lock-tables --events --single-transaction --quick $database_to_backup > $file_path";

                CommandLine::exec($command);

                $this->validateFile($file_path);
            }

            mysqli_close($link);

            $i++;
        }
    }

    private function validateFile($filepath) {
        if (!file_exists($filepath)) {
            throw new Exception("Database archive was not created" . PHP_EOL);
        }

        if (!Filesystem::assertFileMimetype($filepath, 'application/sql')) {
            throw new Exception("Database archive doesn't have the correct file mime type" . PHP_EOL);
        }
    }
}
