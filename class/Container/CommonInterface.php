<?php

namespace Backup\Container;

interface CommonInterface {

    public function create(array $source, string $destination_filename, ?string $destination_folder = null): array;

    public function extract(string $source, string $destination);

}