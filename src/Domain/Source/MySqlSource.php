<?php

namespace Backup\Domain\Source;

class MySqlSource {
    private string $hostname;
    private string $username;
    private ?string $password;
    private string $charset;

    public function __construct(string $hostname, string $username, ?string $password, string $charset) {
        $this->hostname = $hostname;
        $this->username = $username;
        $this->password = $password;
        $this->charset = $charset;
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
