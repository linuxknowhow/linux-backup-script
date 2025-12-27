<?php

namespace Backup\Domain\Source;

use Exception;

class MySqlSource {
    private string $hostname;
    private string $username;
    private ?string $password;
    private string $charset;

    public function __construct(string $hostname, string $username, ?string $password, string $charset) {
        $host = trim($hostname);
        $user = trim($username);
        $charsetNormalized = trim($charset) === '' ? 'utf8mb4' : trim($charset);

        if ($host === '' || $user === '') {
            throw new Exception('MySQL source requires non-empty hostname and username');
        }

        $this->hostname = $host;
        $this->username = $user;
        $this->password = $password;
        $this->charset = $charsetNormalized;
    }

    public function getHostname(): string {
        return $this->hostname;
    }

    public function getUsername(): string {
        return $this->username;
    }

    public function getPassword(): ?string {
        return $this->password;
    }

    public function getCharset(): string {
        return $this->charset;
    }
}
