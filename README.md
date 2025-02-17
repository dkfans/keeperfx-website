KeeperFX Official Website
=========================

The KeeperFX website is a modern webapp written in PHP.

The official website is hosted at https://keeperfx.net

## Screenshot

![KeeperFX Website Homepage](https://v0id.us/uploads/libmN0koKnaDK3ee.png)

## Requirements

- PHP 8.1
- MySQL / MariaDB
- Any webserver (nginx, apache, ...)
- Composer
- Redis (optional)

## Used Technologies / Libraries

- Doctrine
- ...

## Local environment setup

First run the following commands (be sure everything from requirements is installed):

```bash
$ git clone https://github.com/dkfans/keeperfx-website.git keeperfx-website
$ cd keeperfx-website
$ composer install
```
Copy and rename `./.env.dev` to `./.env`. Edit environment variables in this file to your local webstack.

Point your webserver to `./public/index.php`. An example nginx config can be found at [docs/nginx-config.md](docs/nginx-config.md).

For downloading alpha builds locally and serving them, setup the environment variable `APP_ALPHA_PATCH_STORAGE` and point the nginx config for `/downloads/` to this directory.
You'll also need to define a Github token in `APP_ALPHA_PATCH_GITHUB_DOWNLOADER_AUTH_TOKEN` to be able to download the artifacts.
You can get one at [Github > Settings > Developer settings > Personal access tokens > Tokens (classic)](https://github.com/settings/tokens).

After setting the correct database environment variables. Make sure your webserver (+ php) and database are running and setup the database structure using *doctrine/migrations*:

```
$ php ./console migrations:migrate
```

A user account for the admin panel can be created with the following command:

```
$ php ./console user:create <username> <password> admin
```

Everything should be working and deciding on your nginx (or other webserver) config you should be able to access the website at whatever host/port you've chosen.

You can see all console commands using this command:

```
$ php ./console
```

## Roadmap

- ...
- npm, yarn, webpack

## Production update deployment commands

```bash
$ php ./console maintenance:start
$ git reset --hard
$ git pull
$ composer install --no-dev
$ php ./console migrations:migrate
$ php ./console cache:clear
$ php ./console cache:warm
$ php ./console maintenance:stop
```

## Security Issues

If you have found a possible security issue with the KeeperFX website, please contact [security@keeperfx.net](mailto:security@keeperfx.net) privately. We do not have a bug bounty program but we can publish an acknowledgement on our [security acknowledgments page](https://keeperfx.net/security-acknowledgments).
