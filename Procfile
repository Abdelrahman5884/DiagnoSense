web: php artisan migrate --force && (php artisan queue:work --tries=3 & php artisan reverb:start --host=0.0.0.0 --port=8090 & php artisan serve --host=0.0.0.0 --port=$PORT)
