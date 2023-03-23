<?php

namespace Backup\Container;

use Backup\Container\CommonInterface;
use Backup\Container\CommonTrait;
use Assert\Assert;
use Assert\LazyAssertionException;
use Backup\Helper\CommandLine;
use Backup\Helper\Filesystem;

class SevenZip implements CommonInterface {
    use CommonTrait;

    private const EXTENSION = '7z';

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
                    abort("Invalid settings in the 7-Zip archiver settings section of the container providers: '" . $setting_key . "'");
            }
        }
    }

    public function create(array $source, string $destination_filename, ?string $destination_folder = null): array {
        $this->validateCreateParams($source, $destination_filename, $destination_folder);

        if ($destination_folder) {
            $destination = "$destination_folder/$destination_filename." . self::EXTENSION;
        } else {
            $destination = "$destination_filename." . self::EXTENSION;
        }

        $escaped_destination = escapeshellarg($destination);

        $escaped_source = array_map( function($n) { return escapeshellarg($n); }, $source);
        $escaped_source_combined = implode(' ', $escaped_source);

        if ($this->password) {
            $escaped_password = $this->escapePassword($this->password);

            $flag_password = "-mhe=on -p'{$escaped_password}'";
        } else {
            $flag_password = '';
        }

        if ($this->compression_level) {
            $flag_compression_level = "-mx{$this->compression_level}";
        } else {
            $flag_compression_level = '';
        }

        if ($this->volume_size) {
            $flag_volume_size = "-v{$this->volume_size}b";
        } else {
            $flag_volume_size = '';
        }

        $command = "7z $flag_password $flag_compression_level $flag_volume_size a $escaped_destination $escaped_source_combined";

        if ( CommandLine::exec( $command, $this->getWorkingDirectory() ) ) {
            $destination_single = [];
            $destination_multi = [];

            $working_directory = $this->getWorkingDirectory();

            $is_absolute_path = Filesystem::isAbsolutePath($destination);

            if ($is_absolute_path) {
                $glob_path_single = $destination;

                if ( !empty($destination_folder) ) {
                    $glob_path_multi = "$destination_folder/$destination_filename." . self::EXTENSION . '.*';
                } else {
                    $glob_path_multi = "$destination_filename." . self::EXTENSION . '.*';
                }

            } else {
                if ($working_directory) {
                    $glob_path_single = "$working_directory/$destination";

                    if ( !empty($destination_folder) ) {
                        $glob_path_multi = "$working_directory/$destination_folder/$destination_filename." . self::EXTENSION . '.*';
                    } else {
                        $glob_path_multi = "$working_directory/$destination_filename." . self::EXTENSION . '.*';
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
            abort("Cannot create a 7-Zip archive $destination");
        }
    }

    public function extract(string $source, string $destination) {
        // TODO: to implement
    }

    public function setPassword(?string $password) {
        if ( !is_null($password) ) {
            try {
                Assert::lazy()->tryAll()
                    ->that($password, '7-Zip password')->notEmpty("7-Zip password cannot be empty")->string()->betweenLength(1, 127)
                    ->verifyNow();

            } catch (LazyAssertionException $e) {
                abort( $e->getMessage() );

            } catch (\Throwable $e) {
                abort( "Fatal error: " . $e->getMessage() );
            }

            if ( !ctype_print_utf($password) ) {
                abort("7-Zip password cannot contain control characters");
            }

            $this->password = $password;

        } else {
            $this->password = null;
        }
    }

    public function setCompressionLevel(?int $compression_level) {
        try {
            Assert::lazy()->tryAll()
                ->that($compression_level, '7-Zip compression level')
                    ->notEmpty("7-Zip encryption compression level cannot be empty when defined")
                    ->integer()
                    ->between(0, 9, "7-Zip compression level can only be set between 0 and 9 (0-copy mode, 5-default, 9-ultra)")
                ->verifyNow();

        } catch (LazyAssertionException $e) {
            abort( $e->getMessage() );

        } catch (\Throwable $e) {
            abort( "Fatal error: " . $e->getMessage() );
        }

        $this->compression_level = null;
    }

    public function setVolumeSize(?string $volume_size) {
        if ( !is_null($volume_size) ) {
            $regex = "/^([0-9]+)([k,M,G]{1})?$/i";

            try {
                Assert::lazy()->tryAll()
                    ->that($volume_size, '7-Zip volume size')
                        ->notEmpty("7-Zip volume size cannot be empty when defined")
                        ->string()
                        ->regex($regex, "7-Zip volume size is incorrect. Supported binary prefixes: k, M, G, T")
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
                        abort('Internal error: incorrect binary prefix in 7-Zip volume size');
                }

                $volume_size_number = $bytes;

            } else {
                $volume_size_number = (int)$matches[1];
            }

            try {
                Assert::lazy()->tryAll()
                    ->that($volume_size_number, '7-Zip volume size')
                        ->notEmpty("7-Zip volume size cannot be empty")
                        ->integer()
                        ->between(1000, PHP_INT_MAX, "7-Zip volume size can only be set between 1000 bytes and " . PHP_INT_MAX . " bytes")
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

    public function escapePassword(string $password) {
        return str_replace("'", "\'", $password);
    }

}