<?php

namespace Backup\Domain\Source;

class CronSource {
    private string $user;

    public function __construct(string $user) {
        $this->user = $user;
    }

    public function getUser(): string {
        return $this->user;
    }
}
