#!/bin/bash
set -e

cd /var/www/html/backend
composer install --no-interaction

cd /var/www/html/frontend
npm install
npm run build

cd /var/www/html/backend
php bin/init-data.php

exec apache2-foreground
