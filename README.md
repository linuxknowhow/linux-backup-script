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

- PHP 7.4, Tar
- Depending on archivers you want to use: Gzip, 7zip, Rar
- GPG

## How to run it:

```
php /path/to/script/src/backup.php --create
```

## Roadmap

- Add more cloud storage providers: Backblaze, DigitalOcean
- Add SSH support
- Add logging
- Add notifications

## Contact

Please send your questions, feedback and suggestions to:

stephenson.inbox@gmail.com
