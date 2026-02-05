# Start with PHP 8.4 FPM (FastCGI Process Manager)
FROM php:8.5-fpm

# Update package list and install dependencies
RUN apt-get update && apt-get install -y \
    ca-certificates \
    git \
    curl \
    zip \
    unzip \
    p7zip-full \
    cron \
    libssl-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libbz2-dev \
    libicu-dev \
    libjpeg62-turbo-dev \
    libwebp-dev \
    libfreetype6-dev \
    libsocket++-dev

# Clean up to reduce image size
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Configure PHP GD extension
RUN docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype

# Install PHP extensions
RUN docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    intl \
    zip \
    sockets \
    bz2

# Fix dubious file ownership warnings in git
RUN git config --system --add safe.directory '*'

# Get Composer (PHP package manager)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Start php-fpm
CMD ["php-fpm"]

# Document that we use port 9000
EXPOSE 9000
