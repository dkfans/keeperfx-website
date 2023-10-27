#!/bin/bash
php ./console maintenance:start
git clean --force
git reset --hard
git checkout master
git pull
php ./console cache:clear
composer install --no-dev
php ./console migrations:migrate --no-interaction
php ./console cache:warm
php ./console maintenance:stop
