<?php

namespace Backup\Domain\Storage;

use Exception;

class S3Target {
    private string $region;
    private string $bucket;
    private string $folder;
    private string $accessKeyId;
    private string $secretKey;

    public function __construct(string $region, string $bucket, string $folder, string $accessKeyId, string $secretKey) {
        $regionTrimmed = trim($region);
        $bucketTrimmed = trim($bucket, '/');
        $folderTrimmed = trim($folder, '/');
        $accessKeyTrimmed = trim($accessKeyId);
        $secretKeyTrimmed = trim($secretKey);

        if ($regionTrimmed === '' || $bucketTrimmed === '' || $accessKeyTrimmed === '' || $secretKeyTrimmed === '') {
            throw new Exception('S3 target requires region, bucket, access_key_id and secret_key');
        }

        $this->region = $regionTrimmed;
        $this->bucket = $bucketTrimmed;
        $this->folder = $folderTrimmed;
        $this->accessKeyId = $accessKeyTrimmed;
        $this->secretKey = $secretKeyTrimmed;
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
