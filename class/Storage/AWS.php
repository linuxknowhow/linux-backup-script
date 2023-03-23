<?php

namespace Backup\Storage;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Aws\Exception\MultipartUploadException;
use Aws\S3\MultipartUploader;
use Backup\Entity;

class AWS implements CommonInterface {
    private string $backup_name;

    private string $bucket_name;
    private string $folder;
    private string $access_key_id;
    private string $secret_key;

    private S3Client $s3;

    public function __construct($backup_name, $bucket_name, $folder, $access_key_id, $secret_key) {
        $bucket_name_trimmed = trim($bucket_name, '/');
        $folder_trimmed = trim($folder, '/');

        $this->backup_name = $backup_name;
        $this->bucket_name = $bucket_name_trimmed;
        $this->folder = $folder_trimmed;
        $this->access_key_id = $access_key_id;
        $this->secret_key = $secret_key;

        $this->s3 = new S3Client([
            'region'  => $this->region,
            'version' => 'latest',
            'credentials' => [
                'key'    => $this->access_key_id,
                'secret' => $this->secret_key
            ]
        ]);
    }

    public function getListOfBackups() {
        $backups = [];

        if ( !empty($this->folder) ) {
            $prefix = $this->folder . '/' . $this->backup_name;
        } else {
            $prefix = $this->backup_name;
        }

        try {
            $objects = $this->s3->getIterator('ListObjects', [
                'Bucket' => $this->bucket_name,
                'Prefix' => $prefix,
            ]);

            foreach ($objects as $object) {
                $backup_file_name = basename( $object['Key'] );

                $backup_date = str_replace( ['.7z', 'tar.gz', 'tgz' ], '', $backup_file_name);

                if ( preg_match_all('/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/u', $backup_date, $matches, PREG_PATTERN_ORDER) ) {
                    $backup = new Backup( $backup_file_name, $object['Key'], (int)$matches[1][0], (int)$matches[2][0], (int)$matches[3][0] );

                    $backups[] = $backup;
                }
            }

        }  catch (Aws\S3\Exception\S3Exception $e) {
            abort( 'There was an error getting list of backups from the AWS S3:' . PHP_EOL. $e->getMessage() );
        }

        return $backups;
    }

    public function addFile($filepath) {
        $filename = basename($filepath);

        if ( !empty($this->folder) ) {
            $key = "{$this->folder}/{$this->backup_name}/$filename";
        } else {
            $key = "{$this->backup_name}/$filename";
        }

        $uploader = new MultipartUploader($this->s3, $filepath, [
            'Bucket' => $this->bucket_name,
            'Key' => $key
        ]);

        try {
            $result = $uploader->upload();

            // Backup was uploadeded successfully
            if ($result['@metadata']['statusCode'] !== 200) {
                abort("Cannot upload backup file '$filepath' into AWS");
            }

        }  catch (MultipartUploadException $e) {
            abort( 'There was an error uploading backup to the AWS S3:' . PHP_EOL. $e->getMessage() );
        }
    }

    public function cleanupBackups(array $backups) {
        foreach ($backups as $backup) {
            if ( false === $backup->isPreserved() && !empty( $backup->getFilename() ) ) {
                $this->s3->deleteObject([
                    'Bucket' => $this->bucket_name,
                    'Key' => $backup->getFullpath(),
                ]);
            }
        }

    }

}