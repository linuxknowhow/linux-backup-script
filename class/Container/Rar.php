<?php

namespace Backup\Container;

use Backup\Container\CommonInterface;
use Backup\Container\CommonTrait;
use Assert\Assert;
use Assert\LazyAssertionException;
use Backup\Helper\Filesystem;

class Rar implements CommonInterface {
    use CommonTrait;

    private const EXTENSION = 'rar';

    private ?string $password = null;
    private ?int $compression_level = null;
    private ?int $volume_size = null;

    public function __construct( array $settings ) {
        foreach ($settings as $setting_key => $setting_value) {
            switch ($setting_key) {
                case 'password':
                    $this->setPassword($setting_value);
                    break;

                case 'compression_level':
                    $this->setCompressionLevel( (int)$setting_value );
                    break;

                case 'volume_size':
                    $this->setVolumeSize($setting_value);
                    break;

                default:
                    abort("Invalid settings in the RAR archiver settings section of the container providers: '" . $setting_key . "'");
            }
        }
    }

    public function create(array $source, string $destination_filename, ?string $destination_folder = null): array {
        $this->validateCreateParams($source, $destination_filename, $destination_folder);

        if ( !empty($destination_folder) ) {
            $destination = "$destination_folder/$destination_filename." . self::EXTENSION;
        } else {
            $destination = "$destination_filename." . self::EXTENSION;
        }

        $escaped_destination = escapeshellarg($destination);

        $escaped_source = array_map( function($n) { return escapeshellarg($n); }, $source);
        $escaped_source_combined = implode(' ', $escaped_source);

        if ($this->password) {
            $flag_password = '-hp';
        } else {
            $flag_password = '';
        }

        if ($this->compression_level) {
            $flag_compression_level = "-m{$this->compression_level}";
        } else {
            $flag_compression_level = '';
        }

        if ($this->volume_size) {
            $flag_volume_size = "-v{$this->volume_size}b";
        } else {
            $flag_volume_size = '';
        }

        $command = "rar $flag_password $flag_compression_level $flag_volume_size -idq a $escaped_destination $escaped_source_combined";

        $descriptorspec = [
            0 => ["pipe", "r"],    // stdin is a pipe that the child will read from
            1 => ["pipe", "w"],    // stdout is a pipe that the child will write to
            2 => ["pipe", "w"]     // stderr is a pipe that the child will write to
        ];

        // TODO: To remove later and to log instead
        echo $command . PHP_EOL;

        $process = proc_open( $command, $descriptorspec, $pipes, $this->getWorkingDirectory() );

        if ( is_resource($process) ) {
            fwrite($pipes[0], "$this->password\n$this->password\n");

            $stdout = fread( $pipes[1], 1024 );
            $stderr = fread( $pipes[2], 1024 );

            fclose( $pipes[0] );
            fclose( $pipes[1] );
            fclose( $pipes[2] );

            $result = proc_close($process);

            if ( !$result ) {
                $destination_single = [];
                $destination_multi = [];

                $working_directory = $this->getWorkingDirectory();

                $is_absolute_path = Filesystem::isAbsolutePath($destination);

                if ($is_absolute_path) {
                    $glob_path_single = $destination;

                    // A better solution would be this:
                    // $glob_path .= "{.part*,*}";
                    // but the manuals say:
                    // > Note: The GLOB_BRACE flag is not available on some non GNU systems, like Solaris or Alpine Linux.
                    // So we will stick with this for additional compatibility:

                    if ( !empty($destination_folder) ) {
                        $glob_path_multi = "$destination_folder/$destination_filename.part*." . self::EXTENSION;
                    } else {
                        $glob_path_multi = "$destination_filename.part*." . self::EXTENSION;
                    }

                } else {
                    if ($working_directory) {
                        $glob_path_single = "$working_directory/$destination";

                        if ( !empty($destination_folder) ) {
                            $glob_path_multi = "$working_directory/$destination_folder/$destination_filename.part*." . self::EXTENSION;
                        } else {
                            $glob_path_multi = "$working_directory/$destination_filename.part*." . self::EXTENSION;
                        }

                    } else {
                        abort('Fatal error');
                    }
                }

                $destination_single = glob($glob_path_single);

                if ($this->volume_size) {
                    $destination_multi = glob($glob_path_multi);
                }

                $destination = array_merge($destination_single, $destination_multi);

                if ( empty($destination) ) {
                    abort('Could not locate the archive(s): "' . $glob_path_single .  '" or "' . $glob_path_multi . '"');
                }

                return $destination;

            } else {
                abort("Cannot create a RAR archive $destination");
            }

            return !$result;

        } else {
            abort("Cannot open a process in shell when creating a RAR archive");
        }
    }

    public function extract(string $source, string $destination) {
        // TODO: To implement
    }

    public function setPassword(?string $password) {
        if ( !is_null($password) ) {
            try {
                Assert::lazy()->tryAll()
                    ->that($password, 'Rar password')->notEmpty("Rar password cannot be empty")->string()->betweenLength(1, 127)
                    ->verifyNow();

            } catch (LazyAssertionException $e) {
                abort( $e->getMessage() );

            } catch (\Throwable $e) {
                abort( "Fatal error: " . $e->getMessage() );
            }

            if ( !ctype_print_utf($password) ) {
                abort("RAR password cannot contain control characters");
            }

            $this->password = $password;

        } else {
            $this->password = null;
        }
    }

    public function setCompressionLevel(?int $compression_level) {
        if ( !is_null($compression_level) ) {
            try {
                Assert::lazy()->tryAll()
                    ->that($compression_level, 'Rar compression level')
                        ->notEmpty("Rar encryption compression level cannot be empty when defined")
                        ->integer()
                        ->between(0, 5, "RAR compression level can only be set between 0 and 5 (0-store, 3-default, 5-maximal)")
                    ->verifyNow();

            } catch (LazyAssertionException $e) {
                abort( $e->getMessage() );

            } catch (\Throwable $e) {
                abort( "Fatal error: " . $e->getMessage() );
            }
        }

        $this->compression_level = null;
    }

    public function setVolumeSize(?string $volume_size) {
        if ( !is_null($volume_size) ) {
            $regex = "/^([0-9]+)([k,M,G]{1})?$/i";

            try {
                Assert::lazy()->tryAll()
                    ->that($volume_size, 'Rar volume size')
                        ->notEmpty("RAR volume size cannot be empty when defined")
                        ->string()
                        ->regex($regex, "RAR volume size is incorrect. Supported binary prefixes: k, M, G, T")
                    ->verifyNow();

            } catch (LazyAssertionException $e) {
                abort( $e->getMessage() );

            } catch (\Throwable $e) {
                abort( "Fatal error: " . $e->getMessage() );
            }

            preg_match($regex, $volume_size, $matches);

            $binary_prefix = mb_strtolower( $matches[2] ?? '' );

            if ( !empty($binary_prefix) ) {
                $bytes = (int)$matches[1];

                switch ($binary_prefix) {
                    case 'b':
                        break;

                    case 'k':
                        $bytes *= 1024**1;
                        break;

                    case 'm':
                        $bytes *= 1024**2;
                        break;

                    case 'g':
                        $bytes *= 1024**3;
                        break;

                    case 't':
                        $bytes *= 1024**4;
                        break;

                    default:
                        abort('Internal error: incorrect binary prefix in RAR volume size');
                }

                $volume_size_number = $bytes;

            } else {
                $volume_size_number = (int)$matches[1];
            }

            try {
                Assert::lazy()->tryAll()
                    ->that($volume_size_number, 'Rar volume size')
                        ->notEmpty("RAR volume size cannot be empty")
                        ->integer()
                        ->between(1000, PHP_INT_MAX, "RAR volume size can only be set between 1000 bytes and " . PHP_INT_MAX . " bytes")
                    ->verifyNow();

            } catch (LazyAssertionException $e) {
                abort( $e->getMessage() );

            } catch (\Throwable $e) {
                abort( "Fatal error: " . $e->getMessage() );
            }

            $this->volume_size = $volume_size_number;

        } else {
            $this->volume_size = null;
        }
    }

}