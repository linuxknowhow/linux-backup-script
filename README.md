# linux-backup-script

How to start using this script?

**Clone the repo - Do composer install - Edit the config file - Put the bin/console into your cron**

That's it!

## What is it?

This script is a command-line utility for backuping linux servers. It's used for backuping:

- Folders
- MySQL databases
- Cron entries

## Where can it store backups?

- Locally
- AWS S3

## Killer features:

- Simple PHP script running locally on your server. No telemetry, no privacy violation, no weird behaviour, no nonsense.
- Easy to configure: one single self-explanatory config file.
- Protect your backups with strong encryption. You can put your encrypted backup inside of another encrypted backup, like a nested doll. First, create a tar.gz, then encrypt it with 7zip and then encrypt it with GPG. Play with the order of archiving any way you want. Now you can freely upload your backups anywhere without worrying of any cloud service reading the contents without your permission.

## Prerequisites

- PHP 8.2
- PHP extensions: php8.2-mysqli, php8.2-xml, php8.2-simplexml, php8.2-mbstring
- Binaries (auto-checked based on your config): tar/gzip, mysqldump if MySQL sources are configured, 7z if you use 7zip in containers_sequence, gpg if you use gpg, rar if you use rar.

## How to run it:

Run in the command line or using cron:
```
php /some-path/scripts/linux-backup-script/bin/console --create --cleanup
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
7. Run to see if it works and add to cron:

`php /root/scripts/linux-backup-script/bin/console --create --cleanup`

## Config

```
name: "vps"

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

advanced_settings:
    tmp_folder: "/tmp/linux-backup-script/"

### How sequences and lists work

- `containers_sequence`: items run in order; the output of each container feeds the next (e.g., targz then gpg).
- `storage_list`: items are independent targets; each uploaded with the same resulting files (e.g., S3 and local both receive the backup).

Config keys are expected to be stable - new will be added later, but currently used are unlikely to be renamed.
```

## Roadmap

- Add more cloud storage providers: Backblaze, DigitalOcean
- Add sftp upload
- Add logging
- Add notifications

## Contact

Please send your questions, feedback and suggestions to:

stephenson.inbox@gmail.com
