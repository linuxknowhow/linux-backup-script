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
- Depending on archivers you want to use: Gzip, 7zip, Rar
- GPG

## How to run it:

Create and upload backup:
```
php /path/to/script/backup.php --create
```

Delete old backups:
```
php /path/to/script/backup.php --clean
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