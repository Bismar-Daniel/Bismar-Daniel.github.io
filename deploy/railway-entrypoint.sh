#!/bin/sh
set -eu

PORT="${PORT:-8080}"
DB_HOST="${DB_HOST:-${MYSQLHOST:-}}"
DB_PORT="${DB_PORT:-${MYSQLPORT:-3306}}"
DB_NAME="${DB_NAME:-${MYSQLDATABASE:-hall_of_armor}}"
DB_USER="${DB_USER:-${MYSQLUSER:-}}"
DB_PASSWORD="${DB_PASSWORD:-${MYSQLPASSWORD:-}}"

if [ -z "$DB_HOST" ] || [ -z "$DB_USER" ]; then
  echo "Missing MySQL environment variables (DB_HOST/DB_USER or Railway MySQL variables)." >&2
  exit 1
fi

sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

tries=0
until MYSQL_PWD="$DB_PASSWORD" mysqladmin ping -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" --silent; do
  tries=$((tries + 1))
  if [ "$tries" -ge 60 ]; then
    echo "MySQL did not become available before timeout." >&2
    exit 1
  fi
  sleep 2
done

# schema.sql is idempotent and contains only empty tables plus disabled routine definitions.
if [ "${DB_AUTO_MIGRATE:-true}" = "true" ]; then
  tail -n +3 /var/www/html/database/schema.sql | MYSQL_PWD="$DB_PASSWORD" mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" "$DB_NAME"
fi

exec apache2-foreground
