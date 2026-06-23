#!/bin/bash

# Copy the example environment variables
cp .env.docker .env

# Set to development environment
sed -i 's/^APP_ENV=.*/APP_ENV=dev/' .env

# Generate secrets for the MariaDB database
sed -i "s/^MYSQL_PASSWORD=.*/MYSQL_PASSWORD=$(openssl rand -base64 32 | tr -dc 'a-zA-Z0-9' | head -c 32)/" .env && \
sed -i "s/^MYSQL_ROOT_PASSWORD=.*/MYSQL_ROOT_PASSWORD=$(openssl rand -base64 32 | tr -dc 'a-zA-Z0-9' | head -c 32)/" .env

# Setup mailpit
sed -i 's/^APP_SMTP_HOST=.*/APP_SMTP_HOST=mailpit/' .env
sed -i 's/^APP_SMTP_PORT=.*/APP_SMTP_PORT=1025/' .env
sed -i 's/^APP_SMTP_AUTH=.*/APP_SMTP_AUTH=0/' .env
sed -i 's/^APP_SMTP_TLS=.*/APP_SMTP_TLS=0/' .env
sed -i 's/^APP_SMTP_VERIFY_CERT=.*/APP_SMTP_VERIFY_CERT=0/' .env

# Create logs and cache dirs
mkdir -p ./logs ./cache

# Set correct permissions for logs and cache dirs
if [ "$EUID" -eq 0 ]; then
    # We are already root, execute without sudo
    chown 33:$(id -g) ./logs ./cache
    chmod 777 ./logs ./cache
else
    # We are not root. Check if sudo will ask for a password.
    # 'sudo -n true' fails (returns non-zero) if a password is required.
    if ! sudo -n true 2>/dev/null; then
        echo "sudo is required to set correct permissions of logs and cache dirs..."
    fi

    # Run the commands. If sudo -n true succeeded above, this won't prompt for a password.
    sudo chown 33:$(id -g) ./logs ./cache
    sudo chmod 777 ./logs ./cache
fi

# Copy the compose override example configuration
cp compose.override.yml.example compose.override.yml

# Start the docker containers and wait for the healthcheck to pass
echo ""
echo "Starting all docker containers for initial vendor and database setup"
echo "Please wait..."
echo ""
docker compose up -d --wait

# Install composer libs
# This needs to be run as the host user because we install into the host filesystem
docker compose exec -it -u $(id -u) php composer install

# Setup the database
docker compose exec -it -u www-data php ./console migrations:migrate --no-interaction

# Seed the database with defaults
# Admin user details are admin:admin
docker compose exec -it -u www-data php ./console dev:generate-mock-data

# Done!
echo ""
echo ""
echo "If you want to use the following services you still need to enable them in the docker compose override:"
echo "    mailpit:       http://127.0.0.1:5525"
echo "    mitmproxy:     http://127.0.0.1:5580"
echo "    mitmproxy UI:  http://127.0.0.1:5581/?token=keeperfx"
echo ""
echo ""
echo -e "KeeperFX Website available at: \033[4;35mhttp://127.0.0.1:5500\033[0m"
echo ""
echo ""
