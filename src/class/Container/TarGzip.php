<?php

namespace Backup\Container;

use Backup\Container\CommonInterface;
use Backup\Container\CommonTrait;
use Assert\Assert;
use Assert\LazyAssertionException;
use Backup\Helper\CommandLine;
use Backup\Helper\Filesystem;

class TarGzip implements CommonInterface {
    use CommonTrait;

    private const EXTENSION = 'tgz';

    private ?int $compression_level = null;

    public function __construct(array $settings = []) {
        foreach ($settings as $setting_key => $setting_value) {
            switch ($setting_key) {
                case 'compression_level':
                    $this->setCompressionLevel((int)$setting_value);
                    break;

                default:
                    throw new Exception("Invalid settings in the TarGzip archiver settings section of the container providers: '" . $setting_key . "'");
            }
        }
    }

    public function create(array $source, string $destination_filename, ?string $destination_folder = null): array {
        $this->validateCreateParams($source, $destination_filename, $destination_folder);

        if (!empty($destination_folder)) {
            $destination = "$destination_folder/$destination_filename." . self::EXTENSION;
        } else {
            $destination = "$destination_filename." . self::EXTENSION;
        }

        $escaped_destination = escapeshellarg($destination);

        $escaped_source = array_map(function ($n) {
            return escapeshellarg($n);
        }, $source);
        $escaped_source_combined = implode(' ', $escaped_source);

        if ($this->compression_level) {
            $flag_compression_level = "-{$this->compression_level}";
        } else {
            $flag_compression_level = '';
        }

        $command = "tar -czpf - $escaped_source_combined | gzip $flag_compression_level - > $escaped_destination";

        $working_directory = $this->getWorkingDirectory();

        if (CommandLine::exec($command, $working_directory)) {
            $is_absolute_path = Filesystem::isAbsolutePath($destination);

            if ($is_absolute_path) {
                $output_file = $destination;
            } else {
                if ($working_directory) {
                    $output_file = "$working_directory/$destination";
                } else {
                    throw new Exception('Fatal error');
                }
            }

            if (file_exists($output_file)) {
                return [$output_file];
            } else {
                throw new Exception('Could not locate the archive: "' . $output_file .  '"');
            }
        } else {
            throw new Exception("Cannot create a TarGzip archive $destination");
        }
    }

    public function extract(string $source, string $destination) {
        // TODO: to implement
    }

    public function setCompressionLevel(?int $compression_level) {
        try {
            Assert::lazy()->tryAll()
                ->that($compression_level, 'Gzip compression level', "Gzip compression level can only be set between 1 and 9 (1-worst, 6-default, 9-best)")
                    ->integer()
                    ->notEmpty("Gzip encryption compression level cannot be empty when defined")
                    ->between(1, 9)
                ->verifyNow();
        } catch (LazyAssertionException $e) {
            throw new Exception($e->getMessage());
        } catch (\Throwable $e) {
            throw new Exception("Fatal error: " . $e->getMessage());
        }

        $this->compression_level = $compression_level;
    }
}
