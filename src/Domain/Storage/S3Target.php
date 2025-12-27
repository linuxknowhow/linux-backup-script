<?php

namespace Backup\Domain\Storage;

class S3Target {
    private string $region;
    private string $bucket;
    private string $folder;
    private string $accessKeyId;
    private string $secretKey;

    public function __construct(string $region, string $bucket, string $folder, string $accessKeyId, string $secretKey) {
        $this->region = $region;
        $this->bucket = $bucket;
        $this->folder = $folder;
        $this->accessKeyId = $accessKeyId;
        $this->secretKey = $secretKey;
    }

    public function getRegion(): string {
        return $this->region;
    }

    public function getBucket(): string {
        return $this->bucket;
    }

    public function getFolder(): string {
        return $this->folder;
    }

    public function getAccessKeyId(): string {
        return $this->accessKeyId;
    }

    public function getSecretKey(): string {
        return $this->secretKey;
    }
}
