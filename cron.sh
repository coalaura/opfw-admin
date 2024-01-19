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

# Repair file permissions
chown -R www-data:www-data /var/www/opfw-admin/storage
chgrp -R www-data /var/www/opfw-admin/storage
chmod -R ug+rwx /var/www/opfw-admin/storage

echo "Done"