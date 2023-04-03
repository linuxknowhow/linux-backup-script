<?php

namespace Backup\Container;

use Backup\Container\CommonInterface;
use Backup\Container\CommonTrait;
use Assert\Assert;
use Assert\LazyAssertionException;
use Backup\Helper\Filesystem;

class Gpg implements CommonInterface {
    use CommonTrait;

    private const EXTENSION = 'gpg';

    private ?string $password = null;

    public function __construct(array $settings) {
        foreach ($settings as $setting_key => $setting_value) {
            switch ($setting_key) {
                case 'password':
                    $this->setPassword($setting_value);
                    break;

                default:
                    abort("Invalid settings in the GPG settings section of the container providers: '" . $setting_key . "'");
            }
        }
    }

    public function create(array $source, string $destination_filename, ?string $destination_folder = null): array {
        $this->validateCreateParams($source, $destination_filename, $destination_folder);

        $working_directory = $this->getWorkingDirectory();

        $destination_list = [];

        foreach ($source as $source_filename) {
            $source_basename = basename($source_filename);

            if (!empty($destination_folder)) {
                $destination = "$destination_folder/$source_basename." . self::EXTENSION;
            } else {
                $destination = "$source_basename." . self::EXTENSION;
            }

            $escaped_destination = escapeshellarg($destination);

            $escaped_source = escapeshellarg($source_filename);

            if (!$this->password) {
                abort("You forgot to define a password in the GPG settings");
            }

            $command = "gpg --symmetric --cipher-algo AES256 --batch --quiet --yes --passphrase-fd 3 -o $escaped_destination -c $escaped_source";

            $descriptorspec = [
                0 => ["pipe", "r"],    // stdin is a pipe that the child will read from
                1 => ["pipe", "w"],    // stdout is a pipe that the child will write to
                2 => ["pipe", "w"],    // stderr is a pipe that the child will write to
                3 => ["pipe", "r"]     // the pipe to feed the password into the child
            ];

            // TODO: To remove later and to log instead
            echo $command . PHP_EOL;

            $process = proc_open($command, $descriptorspec, $pipes, $this->getWorkingDirectory());

            if (is_resource($process)) {
                fwrite($pipes[3], $this->password);

                fclose($pipes[0]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                fclose($pipes[3]);

                $result = proc_close($process);

                if (!$result) {
                    $is_absolute_path = Filesystem::isAbsolutePath($destination);

                    if ($is_absolute_path) {
                        $output_file = $destination;
                    } else {
                        if ($working_directory) {
                            $output_file = "$working_directory/$destination";
                        } else {
                            abort('Fatal error');
                        }
                    }

                    if (file_exists($output_file)) {
                        $destination_list[] = $output_file;
                    } else {
                        abort('Could not locate the archive: "' . $destination .  '"');
                    }
                } else {
                    abort("Cannot create a GPG encrypted file $destination");
                }
            } else {
                abort("Cannot open a process in shell when creating a GPG encrypted file");
            }
        }

        return $destination_list;
    }

    public function extract(string $source, string $destination) {
        // TODO: To implement
    }

    public function setPassword(?string $password) {
        if (!is_null($password)) {
            try {
                Assert::lazy()->tryAll()
                    ->that($password, 'GPG password')->string()->notEmpty("GPG password cannot be empty")->betweenLength(1, 255)
                    ->verifyNow();
            } catch (LazyAssertionException $e) {
                abort($e->getMessage());
            } catch (\Throwable $e) {
                abort("Fatal error: " . $e->getMessage());
            }

            if (!ctype_print_utf($password)) {
                abort("GPG password cannot contain control characters");
            }

            $this->password = $password;
        } else {
            $this->password = null;
        }
    }
}
