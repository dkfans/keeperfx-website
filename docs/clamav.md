KeeperFX ClamAV scanning
========================

Workshop items can be scanned by ClamAV.

This should be done automatically.



## ClamAV Setup


Default Ubuntu installation:

```sh
apt install clamav clamav-daemon libclamunrar9
```

***libclamunrar9*** is used to scan RAR archives.

Then run the following command to setup the ClamAV daemon if it did not automatically start a configuration wizard:

```sh
dpkg-reconfigure clamav-daemon
```

During the configuration, be sure to add the clamav user to the usergroup that has access to the KeeperFX workshop files.

## App Configuration

Edit the `.env` file and set `APP_CLAMAV_DSN` to the correct connection details for the ClamAV Daemon.

Example UNIX socket:

```sh
APP_CLAMAV_DSN=unix:///var/run/clamav/clamd.ctl
```

Example TCP socket:

```sh
APP_CLAMAV_DSN=tcp://127.0.0.1:3310
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

