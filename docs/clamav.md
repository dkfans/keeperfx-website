KeeperFX ClamAV scanning
========================

Workshop items can be scanned by ClamAV.

This should be done automatically.



## ClamAV Setup


Default Ubuntu installation:

```bash
apt install clamav clamav-daemon libclamunrar9
```

***libclamunrar9*** is used to scan RAR archives.



## App Configuration

Edit the `.env` file and set `APP_CLAMAV_DSN` to the correct one ClamAV Daemon. Example:
```
APP_CLAMAV_DSN=unix:///var/run/clamav/clamd.ctl
```


## App Commands

```bash
./console clamav:scan-new
./console clamav-scan-all
```



## Scan statusses

- WorkshopScanStatus::NOT_SCANNED_YET (0)
- WorkshopScanStatus::SCANNING        (1)
- WorkshopScanStatus::SCANNED         (2)

