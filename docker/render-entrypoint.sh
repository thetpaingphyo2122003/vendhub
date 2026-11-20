#!/bin/sh
set -eu

: "${PORT:=10000}"
sed -ri 's|^Listen [0-9]+$|Listen '"${PORT}"'|' /etc/apache2/ports.conf
sed -ri "s!<VirtualHost \*:[0-9]+>!<VirtualHost *:${PORT}>!" /etc/apache2/sites-available/000-default.conf

if [ "${RUN_DATABASE_INSTALL:-false}" = "true" ]; then
    php bin/install.php
fi

exec apache2-foreground
