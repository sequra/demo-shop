#!/bin/bash
set -e

cd /var/www/html

if command -v composer &> /dev/null; then
    composer install --no-interaction
fi

php bin/init-data.php

exec "$@"
