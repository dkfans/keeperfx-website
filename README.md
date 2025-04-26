![KeeperFX Website Homepage](/public/img/banner/top-banner.png)

KeeperFX Official Website
=========================

The KeeperFX website is a modern webapp written in PHP.

The official website is hosted at: https://keeperfx.net

## Developer Environment Setup

The current way of developing is mostly focused around Docker.
The included Docker Compose file already handles storage and contains the whole stack.
The Docker Compose file is used on the official production server so it makes development much easier.
You can [set up your environment natively](/docs/native-dev-setup.md) but it's highly suggested to just use Docker using the instructions below. 



Download the repository:
```
$ git clone https://github.com/dkfans/keeperfx-website.git
$ cd keeperfx-website
```

Start the containers:
```
$ docker compose up -d
```

Install composer libraries:
```
$ docker compose exec -it -u www-data php composer install
```

Do the database migrations (this sets up the database structure):
```
$ docker compose exec -it -u www-data php ./console migrations:migrate
```

Optional: Make an admin user:
```
$ docker compose exec -it -u www-data php ./console user:create <username> <password> admin
```

Visit the website at: http://127.0.0.1:5500

> **NOTE:**  
> If you are using a lower-end machine you might want to disable the clamd container as it can eat up to 2GB of RAM.


## Security Issues

If you have found a possible security issue with the KeeperFX website, please contact [security@keeperfx.net](mailto:security@keeperfx.net) privately. We do not have a bug bounty program but we can publish an acknowledgement on our [security acknowledgments page](https://keeperfx.net/security-acknowledgments).
