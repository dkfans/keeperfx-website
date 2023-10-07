KeeperFX Cron Tasks
===================


We use [Crunz](https://packagist.org/packages/crunzphp/crunz) to have all our cronjobs defined in PHP code as tasks.
These tasks can be found in the `/tasks` directory.



### Task List

You can get the configured task list by running: `php ./vendor/bin/crunz schedule:list`

Example:

```
+----+------------------------------------------------------------+--------------+---------------------------------------------------------------------------------+
| #  | Task                                                       | Expression   | Command to Run                                                                  |
+----+------------------------------------------------------------+--------------+---------------------------------------------------------------------------------+
| 1  | Send all mails in the mail queue                           | * * * * *    | /usr/bin/php8.1 /var/www/keeperfx-website/console mail:send-queue-all           |
| 2  | Pull the game git repo from github                         | */10 * * * * | /usr/bin/php8.1 /var/www/keeperfx-website/console kfx:pull-repo                 |
| 3  | Handle game git repo commits and create changelogs         | */10 * * * * | /usr/bin/php8.1 /var/www/keeperfx-website/console kfx:handle-commits            |
| 4  | Fetch the game dev wiki from github                        | */10 * * * * | /usr/bin/php8.1 /var/www/keeperfx-website/console kfx:fetch-wiki                |
| 5  | Remove stale password reset tokens                         | 0 0 * * *    | /usr/bin/php8.1 /var/www/keeperfx-website/console user:clear-old-password-reset |
| 6  | Fetch the stable releases from github                      | */10 * * * * | /usr/bin/php8.1 /var/www/keeperfx-website/console kfx:fetch-stable              |
| 7  | Fetch the alpha patches from github                        | */5 * * * *  | /usr/bin/php8.1 /var/www/keeperfx-website/console kfx:fetch-alpha               |
| 8  | Fetch latest version of Unearth                            | */10 * * * * | /usr/bin/php8.1 /var/www/keeperfx-website/console workshop:fetch-unearth        |
| 9  | Fetch latest version of CreatureMaker                      | */10 * * * * | /usr/bin/php8.1 /var/www/keeperfx-website/console workshop:fetch-creature-maker |
| 10 | Scan new workshop files for malware                        | * * * * *    | /usr/bin/php8.1 /var/www/keeperfx-website/console clamav:scan-workshop-new      |
| 11 | Scan all workshop files for malware                        | 0 0 * * *    | /usr/bin/php8.1 /var/www/keeperfx-website/console clamav:scan-workshop-all      |
| 12 | Fetch the forum activity from the Keeper Klan forums       | */10 * * * * | /usr/bin/php8.1 /var/www/keeperfx-website/console kfx:fetch-forum-activity      |
| 13 | Fetch and handle connected Twitch streams playing KeeperFX | */2 * * * *  | /usr/bin/php8.1 /var/www/keeperfx-website/console kfx:handle-twitch-streams     |
+----+------------------------------------------------------------+--------------+---------------------------------------------------------------------------------+
```

_This example might be outdated, all tasks are stored in the `./tasks` directory._



### Setup

The Crunz scheduler needs to be run every minute, so add the following to your crontab:

```
* * * * * cd /var/www/keeperfx-website && ./vendor/bin/crunz schedule:run
```



### Production Backups

This should probably go in the crontab of your root user so you can backup the files **outside** of the webserver directories.

```
0 8 * * * /var/www/keeperfx/backup-daily.sh /var/keeperfx-backup /var/www/keeperfx/storage/avatars /var/www/keeperfx/storage/workshop
```

The `backup-daily.sh` script is located in the root project folder and does the following:
- daily: backup database (it takes the DB details from the .env file)
- daily: backup avatar files
- 3 daily: backup workshop files

_It needs to be ran daily and it will automatically check if it's being ran on the 3th day_
