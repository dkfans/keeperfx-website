KeeperFX Crontab
================

This is the suggested crontab for the automated tasks.

- Fetch Stable releases
- Fetch Alpha patches
- Fetch the Wiki 
- Pull a local copy of the repo (for grabbing commit history)
- Handle commit history between stable releases (for the changelog)
- Fetch Keeper Klan forum activity
- Scan workshop files (new ones and a daily)
- Get latest Unearth release


```
*/10 * * * * php /var/www/keeperfx/console kfx:fetch-stable
*/10 * * * * php /var/www/keeperfx/console kfx:fetch-alpha
*/10 * * * * php /var/www/keeperfx/console kfx:fetch-wiki
*/10 * * * * php /var/www/keeperfx/console kfx:pull-repo
*/10 * * * * php /var/www/keeperfx/console kfx:handle-commits
*/10 * * * * php /var/www/keeperfx/console kfx:fetch-forum-activity
* * * * * php /var/www/keeperfx/console clamav:scan-workshop-new
0 0 * * * php /var/www/keeperfx/console clamav:scan-workshop-all
*/10 * * * * php /var/www/keeperfx/console workshop:fetch-unearth
*/10 * * * * php /var/www/keeperfx/console workshop:fetch-creature-maker
0 0 * * * php /var/www/keeperfx/console user:clear-old-password-reset
```

This list has a race condition between pulling the repo and handling the commits.
Right now this just makes it so the commits come 10 minutes later.


### Backups

This should probably go in the crontab of your root user so you can backup the files **outside** of the webserver directories.

```
0 8 * * * /var/www/keeperfx/backup-daily.sh /var/keeperfx-backup /var/www/keeperfx/storage/avatars /var/www/keeperfx/storage/workshop
```

The `backup-daily.sh` script is located in the root project folder and  does the following:
- daily: backup database (it takes the DB details from the .env file)
- daily: backup avatar files
- 3 daily: backup workshop files

It might be required that you change `mysqldump` and `tar` inside the script to something like `/usr/bin/mysqldump` and `/bin/tar` if you put it in the root crontab.
On most systems you can use `which <name>` to find the location of the binaries.
If this is the case then the backup script should be updated to work on all systems without having to edit it.
