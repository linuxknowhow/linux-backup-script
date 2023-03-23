<?php

namespace Backup\Action;

class Decrypt {
    private $folder;

    private $encryption_list;

    public function __construct($folder, $encryption_list) {
        $this->folder = $folder;
        $this->$encryption_list = $encryption_list;

        // TODO: To check if folder exists
    }

    public function do() {
        
    }

}
