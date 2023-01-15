KeeperFX Official Website
=========================

The KeeperFX website is a modern webapp written in PHP.

The official website is hosted at https://keeperfx.net

## Screenshot

![KeeperFX Website Mainpage](https://v0id.us/uploads/YrdYrzNsMrmY2LEd.png)

## Features

- Automatic Github release pulling
- Automatic changelog generation based on commits
- Automatic Github workflow Alpha build pulling
- Automatic Wiki (Github) pulling
- Alpha builds are downloaded locally as Github artifact require a Github login
- News
- Screenshots
- Admin panel
    - Add and edit news

## Requirements

- PHP 8.1
- MySQL / MariaDB
- Any webserver (nginx, apache, ...)
- Redis (optional)
- Composer

## Technologies / User Libraries

- Doctrine
- ...

## Local environment setup

- ...

## Roadmap

- ...
- npm, yarn, webpack

## Deployment commands

```bash
$ php ./console maintenance:start
$ git reset
$ git pull
$ composer install (--no-dev / --production)
$ php ./console migrations:migrate
$ php ./console cache:clear
$ php ./console maintenance:stop
```
