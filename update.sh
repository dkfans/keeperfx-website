#!/bin/bash

# This script is meant for updating the whole website on a production server.
# It will automatically update the application, the database structure and handle the cache.

# Start maintaince mode
# This makes it so users are unable to interact with the website while we update.
php ./console maintenance:start

# Make sure any local changes to the file structure are reverted
git clean --force
git reset --hard
git checkout master

# Get the new application files from git
git pull

# Clear the cache
php ./console cache:clear

# Install new PHP libraries
composer install --no-dev

# Update the database structure
php ./console migrations:migrate --no-interaction

# Warm the cache
# This makes Twig templates compile, and generates Doctrine Proxies.
php ./console cache:warm

# Disable maintenance
# This allows users to continue using the website again
php ./console maintenance:stop

# Instantly run some other caching tasks
php ./console website:cache-git-commits
php ./console kfx:fetch-discord-info
php ./console kfx:fetch-forum-activity
