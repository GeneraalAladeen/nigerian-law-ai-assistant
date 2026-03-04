FROM php:8.4-fpm-alpine

ARG UID
ARG GID
ENV UID=${UID}
ENV GID=${GID}
ENV PROJECT_ROOT=/var/www/html/project

# Install system dependencies
RUN apk update && \
    apk add --no-cache \
    icu-dev \
    openssl \
    bash \
    nodejs \
    npm \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    git \
    openssh-client \
    autoconf \
    gcc \
    g++ \
    make \
    automake \
    libtool \
    file \
    nasm \
    postgresql-dev \
    postgresql-client \
    gettext \
    linux-headers

# Install core PHP extensions
RUN docker-php-ext-configure gd --with-jpeg && \
    docker-php-ext-install \
        zip gd bcmath opcache exif \
        pdo pdo_pgsql pgsql \
        intl ftp pcntl

# Install Xdebug
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS && \
    pecl install xdebug && \
    docker-php-ext-enable xdebug && \
    rm -rf /tmp/pear && \
    apk del .build-deps

# Install PECL extensions (pcov for coverage, redis for cache/queue)
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS && \
    pecl install pcov && \
    docker-php-ext-enable pcov && \
    pecl install redis && \
    docker-php-ext-enable redis && \
    rm -rf /tmp/pear && \
    apk del .build-deps

# Configure Xdebug
RUN echo "xdebug.mode=develop,debug,coverage" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.start_with_request=trigger" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.client_port=9003" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.log=/tmp/xdebug.log" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Configure user
RUN delgroup dialout && \
    addgroup -g ${GID} --system laravel && \
    adduser -G laravel --system -D -s /bin/sh -u ${UID} laravel

RUN echo "memory_limit = 512M" > /usr/local/etc/php/conf.d/memory-limit.ini && \
    echo "upload_max_filesize = 100M" >> /usr/local/etc/php/conf.d/memory-limit.ini && \
    echo "post_max_size = 100M" >> /usr/local/etc/php/conf.d/memory-limit.ini

USER laravel
WORKDIR $PROJECT_ROOT

CMD ["php", "-v"]
