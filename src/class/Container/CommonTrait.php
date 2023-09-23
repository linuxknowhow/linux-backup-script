<?php

namespace Backup\Container;

use Assert\Assert;
use Assert\LazyAssertionException;
use Exception;

trait CommonTrait {
    private ?string $working_directory = null;

    public function getExtension() {
        return self::EXTENSION;
    }

    public function setWorkingDirectory(string $working_directory) {
        if (file_exists($working_directory) && is_dir($working_directory) && is_readable($working_directory) && is_writable($working_directory)) {
            $this->working_directory = rtrim($working_directory, '/');
        }
    }

    public function getWorkingDirectory() {
        return $this->working_directory;
    }

    private function validateCreateParams(array $source, string $destination_filename, ?string $destination_folder = null) {
        try {
            foreach ($source as $source_file) {
                Assert::lazy()->tryAll()
                    ->that($source_file)
                        ->string()
                        ->notEmpty()
                        ->betweenLength(1, 4096)
                    ->verifyNow();
            }

            Assert::lazy()->tryAll()
                ->that($destination_filename)
                    ->string()
                    ->notEmpty()
                    ->betweenLength(1, 255)
                ->verifyNow();

            if (!is_null($destination_folder)) {
                Assert::lazy()->tryAll()
                    ->that($destination_folder)
                        ->string()
                        ->notEmpty()
                        ->betweenLength(1, 4096)
                    ->verifyNow();
            }

        } catch (LazyAssertionException $e) {
            throw new Exception($e->getMessage());

        } catch (\Throwable $e) {
            throw new Exception("Fatal error: " . $e->getMessage());
        }
    }
}
