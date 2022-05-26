FROM php:7.4-fpm-alpine

RUN apk add --no-cache autoconf openssl-dev g++ make pcre-dev icu-dev zlib-dev libzip-dev && \
    docker-php-ext-install bcmath intl opcache zip sockets && \
    apk del --purge autoconf g++ make && \
    mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

WORKDIR /home/root

COPY . .

ENV PORT=80

COPY docker/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint
ENTRYPOINT ["docker-entrypoint"]

COPY --from=caddy /usr/bin/caddy /usr/local/bin/caddy

RUN caddy validate --config docker/Caddyfile && \
    cp docker/php-fpm.conf /usr/local/etc/php-fpm.d/zz-docker.conf

EXPOSE 80

CMD ["caddy", "run", "--config", "docker/Caddyfile"]
