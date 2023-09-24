# linux-backup-script

## What is it?

This is a linux backup script. It's used for backuping:

- Folders
- MySQL databases
- Cron entries

## Where does it upload backups?

- Locally
- AWS S3

## Killer features:

- YAML config
- Archivers with password protection
- Chaining of archivers
- GPG encryption

## Prerequisites﻿

- PHP 8.2, Tar
- MySQLi extension for PHP
- Depending on archivers you want to use: Gzip, 7zip, Rar
- GPG

## How to run it:

Run in the command line or using cron:
```
php /path/scripts/linux-backup-script/src/backup.php --create --cleanup
```

`--create` will create and upload a backup

`--cleanup` will delete old backups, according to retention period settings

## Installation
1. mkdir /root/scripts/
2. cd /root/scripts/
3. git clone https://github.com/stepcodebox/linux-backup-script.git
4. composer install
5. cp config.example.yml config.yml
6. Edit config.yml according to your needs
7. Run to test and add to cron:

`php /root/scripts/linux-backup-script/src/backup.php --create --cleanup`

## Config

```
name: "vps"

tmp_folder: "/tmp/linux-backup-script/"

sources:
    local_folders:
        - "/etc"
        - "/root"
        - "/var/www"

    mysql:
        - hostname: "127.0.0.1"
          username: "root"
          password:
          charset: "utf8mb4"

    cron:
        - "root"

containers_sequence:
    - targz:
        compression_level: "6"

    - gpg:
        password: "<enter_yours>"

storage_list:
    - aws:
        region: "<enter_yours>"
        bucket: "<enter_yours>"
        access_key_id: "<enter_yours>"
        secret_key: "<enter_yours>"
        folder: "<enter_yours>"

    - local:
        folder: "/mnt/external_storage/"

retention_periods:
    days: "3"
    weeks: "3"
    months: "3"
    years: "3"
```

## Roadmap

- Add more cloud storage providers: Backblaze, DigitalOcean
- Add SSH support
- Add logging
- Add notifications

## Contact

Please send your questions, feedback and suggestions to:

stephenson.inbox@gmail.com

## Did you find it useful?

Consider donating to the [Ukrainian Army Forces](https://savelife.in.ua/en/donate-en/#donate-army-card-monthly).