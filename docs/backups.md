KeeperFX website backups
========================

This is a sample crontab for a production server running the website:

```
# KeeperFX.net backups
0 6 * * * docker exec kfx-mariadb mariadb-dump --all-databases -uroot -p"<database password>" > "/mnt/keeperfx-storage/backup/$(date +\%Y-\%m-\%d)-database.sql"
0 6 */3 * * /bin/tar -czf "/mnt/keeperfx-storage/backup/$(date +"%Y-%m-%d")-workshop.tar.gz" "/mnt/keeperfx-storage/workshop"
0 6 */3 * * /bin/tar -czf "/mnt/keeperfx-storage/backup/$(date +"%Y-%m-%d")-avatars.tar.gz" "/mnt/keeperfx-storage/avatar"
0 6 * * 1 /bin/tar -czf "/mnt/keeperfx-storage/backup/$(date +"%Y-%m-%d")-news-img.tar.gz" "/mnt/keeperfx-storage/news-img"
```

- Database dump: Everyday at 6 AM
- Workshop files backup: Every 3 days at 6 AM
- Avatar backup: Every 3 days at 6 AM
- News images backup: Every Monday at 6 AM

It's best if backups are stored in a location that is not accessible from any of the docker containers.
