<?php

namespace Backup\Sequence;

use Backup\Storage\CommonInterface;
use Backup\Sequence\Sequence;
use Backup\Storage\AWS;
use Backup\Storage\Local;
use Backup\Storage\SSH;

class StorageSequence extends Sequence {
    protected function processConfigItem($key, $settings) {
        switch ($key) {
            case 'local':
                $this->array[] = new Local($settings);

                break;

            case 'aws':
                $this->array[] = new AWS($settings);

                break;

            default:
                throw new Exception('The list of storage providers in the config file contains an incorrect element: \'' . $key . '\'');
        }
    }

    public function current(): CommonInterface {
        return $this->array[ $this->position ];
    }
}
