#!/bin/bash

# Path to your instance without trailing slash
cd /var/www/opfw-admin

for directory in ./envs/*/; do
    [ -L "${d%/}" ] && continue

    cluster="$(basename -- $directory)"

    # Skip the "auth" cluster
    if [ "$cluster" = "auth" ]; then
        continue
    fi

    echo "Running $cluster cronjobs"

    php artisan cron --cluster=$cluster
done

echo "Done"