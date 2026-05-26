web: php artisan migrate --force && php artisan reverb:start --debug --host=0.0.0.0 --port=8090 & php artisan queue:work --tries=1 -vvv & php artisan serve --host=0.0.0.0 --port=$PORT
