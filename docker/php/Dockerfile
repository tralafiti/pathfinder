FROM php:7.1-fpm-alpine

ENV SUPERCRONIC_URL=https://github.com/aptible/supercronic/releases/download/v0.1.5/supercronic-linux-amd64 \
    WEBSOCKET_URL=https://github.com/exodus4d/pathfinder_websocket/archive/v1.1.1.tar.gz

RUN apk add --no-cache --virtual .persistent-deps \
        # for ext-zmq
        libzmq \
        # for ext-event
        libevent \
        # for ext-gd
        libpng \
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
        redis-3.1.6 \
        event-2.3.0 \
    && docker-php-ext-enable \
        zmq \
        redis \
    && docker-php-ext-enable event --ini-name xx-docker-php-ext-event.ini \
    && apk del .build-deps \
    && rm -rf /tmp/*
    
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && php -r "unlink('composer-setup.php');"

RUN apk add --no-cache --virtual .install-deps \
        wget \
        tar \
    && cd /srv \
    && wget -O /tmp/pathfinder_websocket.tar.gz "${WEBSOCKET_URL}" \
    && tar xf /tmp/pathfinder_websocket.tar.gz --strip-components=1 \
    && composer install --no-dev --prefer-dist \
    && rm /tmp/pathfinder_websocket.tar.gz \
    && wget -O /usr/local/bin/supercronic "${SUPERCRONIC_URL}" \
    && chmod +x /usr/local/bin/supercronic \
    && apk del .install-deps

CMD ["supervisord", "-c", "/etc/supervisord.conf"]
