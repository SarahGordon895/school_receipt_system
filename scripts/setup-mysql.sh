#!/bin/sh
# Create the FTRS MySQL database (XAMPP on macOS/Linux).
# Start MySQL in XAMPP Manager first, then run: sh scripts/setup-mysql.sh

set -e

MYSQL_BIN="${MYSQL_BIN:-/Applications/XAMPP/xamppfiles/bin/mysql}"
DB_NAME="${DB_NAME:-school_receipts}"

if [ ! -x "$MYSQL_BIN" ]; then
  MYSQL_BIN="$(command -v mysql || true)"
fi

if [ -z "$MYSQL_BIN" ]; then
  echo "mysql client not found. Install XAMPP MySQL or set MYSQL_BIN."
  exit 1
fi

echo "Creating database: $DB_NAME"
"$MYSQL_BIN" -u root -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

cd "$(dirname "$0")/.."
php artisan config:clear
php artisan migrate --force
echo "MySQL ready. Run: php artisan db:seed --class=DemoDataSeeder --force  (or php artisan ftrs:install)"
