# Start with FrankenPHP 8.4
FROM dunglas/frankenphp:1.4-php8.4

# Update package list and install server dependencies
RUN apt-get update && apt-get install -y \
    ca-certificates \
    git \
    curl \
    zip \
    unzip \
    p7zip-full \
    cron \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions using the built-in helper
RUN install-php-extensions \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    intl \
    zip \
    opcache \
    sockets \
    bz2

# Fix dubious file ownership warnings in git
RUN git config --system --add safe.directory '*'

# Get Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www
