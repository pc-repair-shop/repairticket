#!/bin/sh
set -eu

mkdir -p /data/tickets
touch /data/counter.txt
chown -R www-data:www-data /data

php-fpm -D
exec nginx -g 'daemon off;'
