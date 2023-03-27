<?php

namespace Backup\Storage;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Aws\Exception\MultipartUploadException;
use Aws\S3\MultipartUploader;
use Backup\Entity;
use Assert\Assert;
use Assert\LazyAssertionException;

class AWS implements CommonInterface {
    private string $region;
    private string $bucket;
    private string $folder;
    private string $access_key_id;
    private string $secret_key;

    private S3Client $s3;

    public function __construct(array $settings) {
        $settings_keys = array_keys($settings);

        $allowed_settings = [
            'region',
            'bucket',
            'access_key_id',
            'secret_key',
            'folder'
        ];

        foreach ($settings_keys as $setting_key) {
            if ( !in_array($setting_key, $allowed_settings) ) {
                abort("Invalid setting in the AWS settings section of the storage providers found: '" . $setting_key . "'");
            }
        }

        try {
            Assert::lazy()->tryAll()
                ->that( $settings['region'] )
                    ->string('Invalid AWS region setting')
                    ->notEmpty('AWS region cannot be empty')
                    ->betweenLength(1, 255, 'Invalid AWS region setting')
                ->that( $settings['bucket'] )
                    ->string('Invalid AWS bucket setting')
                    ->notEmpty('AWS bucket cannot be empty')
                    ->betweenLength(1, 255, 'Invalid AWS bucket setting')
                ->that( $settings['region'] )
                    ->string('Invalid AWS access_key_id setting')
                    ->notEmpty('AWS access_key_id cannot be empty')
                    ->betweenLength(1, 255, 'Invalid AWS access_key_id setting')
                ->that( $settings['region'] )
                    ->string('Invalid AWS secret_key setting')
                    ->notEmpty('AWS secret_key cannot be empty')
                    ->betweenLength(1, 255, 'Invalid AWS secret_key setting')
                ->that( $settings['folder'] )
                    ->string('Invalid AWS folder setting')
                    ->notEmpty('AWS folder cannot be empty')
                    ->betweenLength(1, 255, 'Invalid AWS folder setting')
                ->verifyNow();

        } catch (LazyAssertionException $e) {
            abort( $e->getMessage() );

        } catch (\Throwable $e) {
            abort( "Fatal error: " . $e->getMessage() );
        }

        $bucket_trimmed = trim( $settings['bucket'], '/' );
        $folder_trimmed = trim( $settings['folder'], '/' );

        $this->region = $settings['region'];
        $this->bucket = $bucket_trimmed;
        $this->folder = $folder_trimmed;
        $this->access_key_id = $settings['access_key_id'];
        $this->secret_key = $settings['secret_key'];

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
            $prefix = $this->folder;
        } else {
            $prefix = '';
        }

        try {
            $objects = $this->s3->getIterator('ListObjects', [
                'Bucket' => $this->bucket,
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
            $key = "{$this->folder}/$filename";
        } else {
            $key = $filename;
        }

        $uploader = new MultipartUploader($this->s3, $filepath, [
            'Bucket' => $this->bucket,
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
                    'Bucket' => $this->bucket,
                    'Key' => $backup->getFullpath(),
                ]);
            }
        }

    }

}