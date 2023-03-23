#!/bin/bash
php ./console maintenance:start
git checkout .
git pull
composer install --no-dev
php ./console migrations:migrate --no-interaction
php ./console cache:clear
php ./console cache:warm
php ./console maintenance:stop
