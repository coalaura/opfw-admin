#!/bin/bash

cd /var/www/html

php artisan cron

# Repair file permissions
chown -R www-data:www-data storage
chgrp -R www-data storage
chmod -R ug+rwx storage