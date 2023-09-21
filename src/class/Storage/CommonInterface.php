<?php

namespace Backup\Storage;

interface CommonInterface {

    public function getListOfBackups(string $backup_name);

    public function addFile(string $filepath);

    public function cleanupBackups(array $backups);

}