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


```
*/10 * * * * php /var/www/keeperfx/console kfx:fetch-stable
*/10 * * * * php /var/www/keeperfx/console kfx:fetch-alpha
*/10 * * * * php /var/www/keeperfx/console kfx:fetch-wiki
*/10 * * * * php /var/www/keeperfx/console kfx:pull-repo
*/10 * * * * php /var/www/keeperfx/console kfx:handle-commits
*/10 * * * * php /var/www/keeperfx/console kfx:fetch-forum-activity
* * * * * php /var/www/keeperfx/console clamav:scan-workshop-new
0 0 * * * php /var/www/keeperfx/console clamav:scan-workshop-all
```

This list has a race condition between pulling the repo and handling the commits.
Right now this just makes it so the commits come 10 minutes later.
