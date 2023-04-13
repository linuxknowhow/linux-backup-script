<?php

namespace Backup\Sequence;

use Backup\Container\CommonInterface;
use Backup\Sequence\Sequence;
use Backup\Container\TarGzip;
use Backup\Container\SevenZip;
use Backup\Container\Rar;
use Backup\Container\Gpg;

class ContainersSequence extends Sequence {
    protected function processConfigItem($key, $settings) {
        switch ($key) {
            case 'targz':
            case 'targzip':
            case 'tar.gz':
            case 'tar.gzip':
                $this->array[] = new TarGzip($settings);

                break;

            case '7z':
            case '7zip':
                $this->array[] = new SevenZip($settings);

                break;

            case 'rar':
                $this->array[] = new Rar($settings);

                break;

            case 'gpg':
                $this->array[] = new Gpg($settings);

                break;

            default:
                throw new Exception('The sequence of containers in the config file contains an incorrect element: \'' . $key . '\'');
        }
    }

    public function current(): CommonInterface {
        return $this->array[ $this->position ];
    }
}
