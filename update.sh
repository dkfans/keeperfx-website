#!/bin/bash

# This script is meant for updating the whole website on a production server.
# It will automatically update the application, the database structure and handle the cache.

# Start maintenance mode
# This makes it so users are unable to interact with the website while we update.
php ./console maintenance:start

# Make sure any local changes to the file structure are reverted
git clean --force
git reset --hard
git checkout master

# Get the new application files from git
git pull

# Clear the cache
# The -i parameter makes it ignore user sessions
php ./console cache:clear -i

# Install new PHP libraries
composer install --no-dev

# Update the database structure
php ./console migrations:migrate --allow-no-migration --no-interaction

# Warm the cache
# This makes Twig templates compile, and generates Doctrine Proxies.
php ./console cache:warm

# Disable maintenance
# This allows users to continue using the website again
php ./console maintenance:stop

# Run optional caching tasks
# These are not required for the webapp to function correctly
php ./console kfx:fetch-discord-info
php ./console kfx:fetch-forum-activity
php ./console website:cache-git-commits
