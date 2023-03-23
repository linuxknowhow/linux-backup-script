<?php

namespace Backup\Storage;

interface CommonInterface {

    public function getListOfBackups();

    public function addFile(string $filepath);

    public function cleanupBackups(array $backups);

}
