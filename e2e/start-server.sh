#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DB_PATH="${E2E_DB_DATABASE:-$ROOT/database/e2e.sqlite}"
PORT="${E2E_PORT:-8000}"

export APP_ENV=local
export APP_DEBUG=true
export APP_URL="http://127.0.0.1:${PORT}"
export DB_CONNECTION=sqlite
export DB_DATABASE="$DB_PATH"
export DB_URL=
export SESSION_DRIVER=file
export CACHE_STORE=file
export QUEUE_CONNECTION=sync
export MAIL_MAILER=array
export BROADCAST_CONNECTION=null
export SANCTUM_STATEFUL_DOMAINS="127.0.0.1,127.0.0.1:${PORT},localhost,localhost:${PORT}"

cd "$ROOT"
php artisan config:clear --ansi >/dev/null

# server.php uses getcwd() as the public path, so start from public/.
cd "$ROOT/public"
exec php -S "127.0.0.1:${PORT}" "$ROOT/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php"
