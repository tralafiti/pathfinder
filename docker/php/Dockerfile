FROM php:7.2-fpm-alpine3.8

RUN apk add --no-cache --virtual .persistent-deps \
        # for ext-zmq
        libzmq \
        # for ext-event
        libevent \
        openssl \
        # for ext-gd
        libpng \
        # for composer depencendies
        git \
        # for container (running cron, php and socket)
        supervisor

RUN apk add --no-cache --virtual .build-deps \
        # common build stuff
        autoconf cmake file g++ gcc libc-dev pcre-dev make pkgconf re2c \
        # for ext-zmq
        zeromq-dev \
        # for ext-event
        libevent-dev \
        openssl-dev \
        # for ext-gd \
        libpng-dev \
    && docker-php-ext-configure pdo_mysql --with-pdo-mysql \
    && docker-php-ext-install \
        pdo_mysql \
        gd \
        # for ext-event
        sockets \
    && pecl install \
        zmq-1.1.3 \
        redis-4.2.0 \
        event-2.4.3 \
    && docker-php-ext-enable \
        zmq \
        redis \
    && docker-php-ext-enable event --ini-name xx-docker-php-ext-event.ini \
    && apk del .build-deps \
    && rm -rf /tmp/*
    
COPY --from=composer:1 /usr/bin/composer /usr/bin/composer

ENV SUPERCRONIC_URL=https://github.com/aptible/supercronic/releases/download/v0.1.8/supercronic-linux-amd64 \
    WEBSOCKET_URL=https://github.com/exodus4d/pathfinder_websocket/archive/v1.1.1.tar.gz

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

CMD ["supervisord", "-c", "/etc/supervisord.conf"]
