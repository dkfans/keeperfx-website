#!/bin/bash
php ./console maintenance:start
git pull
php ./console migrations:migrate --no-interaction
php ./console cache:clear
php ./console cache:warm
php ./console maintenance:stop
