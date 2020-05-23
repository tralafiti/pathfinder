FROM node:11.10-alpine

RUN apk add --no-cache graphicsmagick gifsicle libwebp-tools build-base autoconf automake libtool libpng-dev jpeg-dev tiff-dev nasm file pkgconf zlib zlib-dev

ARG USER_ID
RUN sed -i -e "s/^\(node:[^:]\):[0-9]*:/\1:${USER_ID}:/" /etc/passwd

USER node

WORKDIR /var/www/html
