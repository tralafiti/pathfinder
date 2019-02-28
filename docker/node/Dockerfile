FROM node:11.10-alpine

RUN apk add --no-cache --virtual .persistent-deps \
        # for compass
        ruby

RUN apk add --no-cache --virtual .build-deps \
        # for compass
        ruby-dev \
        libffi-dev \
        build-base \
    && gem install --no-rdoc --no-ri compass \
    && apk del .build-deps \
    && rm -rf /tmp/*

ARG USER_ID
RUN sed -i -e "s/^\(node:[^:]\):[0-9]*:/\1:${USER_ID}:/" /etc/passwd

USER node

WORKDIR /var/www/html
