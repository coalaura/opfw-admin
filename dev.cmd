@echo off

php artisan config:clear
php artisan view:clear
php artisan route:clear

localhost -ro -c "..\.ssl\localhost.crt" -k "..\.ssl\localhost.key"
