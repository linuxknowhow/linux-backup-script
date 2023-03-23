<?php

namespace Backup\Container;

use Assert\Assert;
use Assert\LazyAssertionException;

trait CommonTrait {
    private ?string $working_directory = null;

    public function getExtension() {
        return self::EXTENSION;
    }

    public function setWorkingDirectory(string $working_directory) {
        if ( file_exists($working_directory) && is_dir($working_directory) && is_readable($working_directory) && is_writable($working_directory) ) {
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
                        ->notEmpty()
                        ->string()
                        ->betweenLength(1, 4096)
                    ->verifyNow();
            }

        Assert::lazy()->tryAll()
            ->that($destination_filename)
                ->notEmpty()
                ->string()
                ->betweenLength(1, 255)
            ->verifyNow();

        if ( !is_null($destination_folder) ) {
            Assert::lazy()->tryAll()
                ->that($destination_folder)
                    ->notEmpty()
                    ->string()
                    ->betweenLength(1, 4096)
                ->verifyNow();
        }

        } catch (LazyAssertionException $e) {
            abort( $e->getMessage() );

        } catch (\Throwable $e) {
            abort( "Fatal error: " . $e->getMessage() );
        }
    }

}
