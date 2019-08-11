FROM php:7.3-fpm-alpine

RUN apk add --no-cache --virtual .persistent-deps \
        # for ext-event
        libevent \
        openssl \
        # for ext-gd
        freetype \
        libpng \
        # for composer depencendies
        git \
        # for container (running cron, php and socket)
        supervisor

RUN apk add --no-cache --virtual .build-deps \
        # common build stuff
        autoconf cmake file g++ gcc libc-dev pcre-dev make pkgconf re2c \
        # for ext-event
        libevent-dev \
        openssl-dev \
        # for ext-gd \
        freetype-dev \
        libpng-dev \
    && docker-php-ext-configure pdo_mysql --with-pdo-mysql \
    && docker-php-ext-configure gd \
         --with-gd \
         --with-freetype-dir=/usr/include/ \
         --with-png-dir=/usr/include/ \
    && docker-php-ext-install \
        pdo_mysql \
        gd \
        # for ext-event
        sockets \
    && pecl install \
        redis-4.3.0 \
        event-2.5.0 \
    && docker-php-ext-enable \
        redis \
    && docker-php-ext-enable event --ini-name xx-docker-php-ext-event.ini \
    && apk del .build-deps \
    && rm -rf /tmp/*
    
COPY --from=composer:1 /usr/bin/composer /usr/bin/composer

ENV SUPERCRONIC_URL=https://github.com/aptible/supercronic/releases/download/v0.1.8/supercronic-linux-amd64 \
    WEBSOCKET_URL=https://github.com/exodus4d/pathfinder_websocket/archive/v1.2.1.tar.gz

RUN apk add --no-cache --virtual .install-deps \
        wget \
        tar \
    && cd /srv \
    && wget -O /tmp/pathfinder_websocket.tar.gz "${WEBSOCKET_URL}" \
    && tar xf /tmp/pathfinder_websocket.tar.gz --strip-components=1 \
    && composer install --no-dev --prefer-dist --optimize-autoloader \
    && rm /tmp/pathfinder_websocket.tar.gz \
    && wget -O /usr/local/bin/supercronic "${SUPERCRONIC_URL}" \
    && chmod +x /usr/local/bin/supercronic \
    && apk del .install-deps

ARG USER_ID
RUN apk add --no-cache --virtual .usermod-deps \
        shadow \
    && usermod -u ${USER_ID} www-data \
    && apk del .usermod-deps

ARG PHP_ENV
COPY supercronictab /etc/supercronictab
COPY supervisord.conf /etc/supervisord.conf
COPY php-fpm.conf /usr/local/etc/php-fpm.conf
COPY php-common.ini /usr/local/etc/php/conf.d/50-settings-common.ini
COPY php-${PHP_ENV}.ini /usr/local/etc/php/conf.d/51-settings-env.ini

CMD ["supervisord", "-c", "/etc/supervisord.conf"]
