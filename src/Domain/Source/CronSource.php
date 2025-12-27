<?php

namespace Backup\Domain\Source;

use Exception;

class CronSource {
    private string $user;

    public function __construct(string $user) {
        $trimmed = trim($user);

        if ($trimmed === '') {
            throw new Exception('Cron source user cannot be empty');
        }

        $this->user = $trimmed;
    }

    public function getUser(): string {
        return $this->user;
    }
}
