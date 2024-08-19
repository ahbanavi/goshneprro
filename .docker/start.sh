#!/bin/bash

set -e

role=${CONTAINER_ROLE:-app}

env=${APP_ENV:-production}

if [ "$env" != "local" ]; then
    echo "Caching configuration..."
    (cd /var/www/html && php artisan optimize:clear && php artisan optimize)
fi

if [ "$role" = "app" ]; then

    exec php-fpm

elif [ "$role" = "worker" ]; then

    echo "Running the worker..."
    php /var/www/html/artisan queue:work -v --sleep=15 --tries=5 --max-time=3600

elif [ "$role" = "scheduler" ]; then

    echo "Scheduler role"
    while true
    do
      php /var/www/html/artisan schedule:run --no-interaction >> /dev/null 2>&1 &
      sleep 60
    done

else
    echo "Could not match the container role \"$role\""
    exit 1
fi
