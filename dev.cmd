@echo off

php artisan config:clear
php artisan view:clear
php artisan route:clear

localhost -ro -c "..\.https\localhost.pem" -k "..\.https\localhost-key.pem"
