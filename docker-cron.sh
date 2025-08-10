#!/bin/bash

cd /var/www/html

echo "Running cronjobs..."
php artisan cron

# Repair file permissions
chown -R www-data:www-data storage
chgrp -R www-data storage
chmod -R ug+rwx storage

echo "Finished cronjobs."
