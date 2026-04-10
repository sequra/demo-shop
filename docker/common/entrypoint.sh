#!/usr/bin/env bash

set -e

# Generate .env from ECS environment variables
# The application reads config via Config::load('.env')
cat > /var/www/html/.env <<EOF
APP_ENV=${APP_ENV:-production}
SEQURA_ACCOUNT_KEY=${SEQURA_ACCOUNT_KEY}
SEQURA_ACCOUNT_SECRET=${SEQURA_ACCOUNT_SECRET}
SEQURA_ENCRYPTION_KEY=${SEQURA_ENCRYPTION_KEY}
SEQURA_WEBHOOK_BASE_URL=${SEQURA_WEBHOOK_BASE_URL}
EOF

# Initialize SeQura data (fetches deployments, validates credentials)
php /var/www/html/bin/init-data.php

exec php-fpm
