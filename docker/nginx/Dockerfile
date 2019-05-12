FROM nginx:1.16-alpine

COPY http.conf /etc/nginx/conf.d/00-http.conf
COPY vhost.conf /etc/nginx/conf.d/99-vhost.conf
COPY ssl /etc/nginx/ssl
COPY auth /etc/nginx/auth
