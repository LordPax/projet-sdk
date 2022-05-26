#!/bin/sh
set -e

# first arg is `-f` or `--some-option`
if [ "${1#-}" != "$1" ]; then
        set -- php-fpm "$@"
fi

if [ "$1" != "caddy" ]; then
        exec docker-php-entrypoint "$@"
else
        docker-php-entrypoint php-fpm -D
        exec "$@"
fi