<?php

namespace Backup\Sequence;

use Iterator;
use Countable;

abstract class Sequence implements Iterator, Countable {
    protected $position = 0;

    protected array $array;

    public function __construct(?array $items) {
        $this->array = [];

        if ( !empty($items) && is_array($items) ) {
            foreach ($items as $key => $item) {
                // List of items
                if ( is_numeric($key) && is_array($item) ) {
                    $this->preProcessConfigItem($item);

                // Single item, no list
                } elseif ( is_string($key) ) {
                    $this->preProcessConfigItem($items);

                    return;

                } else {
                    abort('The list of containers in the settings is incorrect');
                }
            }

        } else {
            abort('The list of containers in the settings is empty or incorrect');
        }
    }

    private function preProcessConfigItem($item) {
        $key = key($item);
        $settings = reset($item);

        if ( !is_string($key) || !is_array($settings) ) {
            abort("The config file contains errors");
        }

        $key_trimmed = trim($key);
        $key_trimmed_lowercase = mb_strtolower($key_trimmed);

        $this->processConfigItem($key_trimmed_lowercase, $settings);
    }

    abstract protected function processConfigItem($key, $settings);

    public function rewind(): void {
        $this->position = 0;
    }

    abstract public function current();

    #[\ReturnTypeWillChange]
    public function key() {
        return $this->position;
    }

    public function next(): void {
        ++$this->position;
    }

    public function valid(): bool {
        return isset( $this->array[ $this->position ] );
    }

    public function count(): int {
        return count( $this->array );
    }

}
