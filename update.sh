#!/bin/bash
php ./console maintenance:start
git checkout .
git pull
php ./console cache:clear
composer install --no-dev
php ./console migrations:migrate --no-interaction
php ./console cache:warm
php ./console maintenance:stop
