#!/bin/sh
set -eu

# Named volumes avoid Windows bind-mount permission mismatches. The FPM master
# process starts as root and its workers run as www-data.
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/testing storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

exec "$@"
