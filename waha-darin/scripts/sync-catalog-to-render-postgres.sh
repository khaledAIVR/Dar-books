#!/usr/bin/env bash
# Re-copy catalog from local MySQL (mysql_source) into default Postgres after you have
# normalized images locally (e.g. php artisan media:publish-from-storage).
#
# Usage:
#   export DATABASE_URL='postgresql://USER:PASS@HOST:5432/DB?sslmode=require'
#   ./scripts/sync-catalog-to-render-postgres.sh
#
# Optional overrides:
#   MYSQL_SOURCE_DATABASE MYSQL_SOURCE_HOST MYSQL_SOURCE_USERNAME MYSQL_SOURCE_PASSWORD

set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

if [[ -z "${DATABASE_URL:-}" ]]; then
  echo "error: set DATABASE_URL to your Render (or other) PostgreSQL URL, e.g. external URL with sslmode=require" >&2
  exit 1
fi

export DB_CONNECTION=pgsql
export MYSQL_SOURCE_DATABASE="${MYSQL_SOURCE_DATABASE:-darin2}"
export MYSQL_SOURCE_HOST="${MYSQL_SOURCE_HOST:-127.0.0.1}"
export MYSQL_SOURCE_USERNAME="${MYSQL_SOURCE_USERNAME:-root}"
export MYSQL_SOURCE_PASSWORD="${MYSQL_SOURCE_PASSWORD:-}"

php artisan data:import-catalog-from-mysql --force --no-interaction
echo "Done. Redeploy the web service if public/media was updated in Git so the image includes cover files."
