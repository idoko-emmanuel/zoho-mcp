#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
#  zoho-mcp: Claude Code plugin setup
#
#  Claude Code copies the plugin into a versioned cache dir (CLAUDE_PLUGIN_ROOT)
#  that is replaced on every update, so the Laravel app runs from a copy in
#  CLAUDE_PLUGIN_DATA, which survives updates. That keeps vendor/, .env, the
#  SQLite database and the saved Zoho OAuth tokens across plugin updates.
#
#  Runs from the SessionStart hook and before every MCP server start. It is
#  idempotent and fast once the app is set up for the current plugin version.
#
#  stdout is kept clean on purpose (the MCP stdio channel and SessionStart
#  context both read it). All output goes to $CLAUDE_PLUGIN_DATA/setup.log.
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail

ROOT="${CLAUDE_PLUGIN_ROOT:?CLAUDE_PLUGIN_ROOT is not set}"
DATA="${CLAUDE_PLUGIN_DATA:?CLAUDE_PLUGIN_DATA is not set}"
APP="$DATA/app"
LOG="$DATA/setup.log"
LOCK="$DATA/.setup.lock"

mkdir -p "$DATA"
exec 3>&2 1>>"$LOG" 2>&1

log()  { echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*"; }
fail() { log "ERROR: $*"; echo "zoho-mcp: $* (log: $LOG)" >&3; exit 1; }

# Credentials come from the plugin's userConfig: as ZOHO_* in the MCP server
# env, or as CLAUDE_PLUGIN_OPTION_* in the SessionStart hook env.
ZOHO_CLIENT_ID="${ZOHO_CLIENT_ID:-${CLAUDE_PLUGIN_OPTION_CLIENT_ID:-}}"
ZOHO_CLIENT_SECRET="${ZOHO_CLIENT_SECRET:-${CLAUDE_PLUGIN_OPTION_CLIENT_SECRET:-}}"
ZOHO_REGION="${ZOHO_REGION:-${CLAUDE_PLUGIN_OPTION_REGION:-}}"

# ── Lock: the hook and the MCP server can both start at session start ────────
acquire_lock() {
  local waited=0
  until mkdir "$LOCK" 2>/dev/null; do
    local pid
    pid="$(cat "$LOCK/pid" 2>/dev/null || true)"
    if [[ -n "$pid" ]] && ! kill -0 "$pid" 2>/dev/null; then
      log "Removing stale lock from pid $pid"
      rm -rf "$LOCK"
      continue
    fi
    if (( waited >= 600 )); then
      fail "Timed out waiting for another setup run to finish"
    fi
    sleep 1
    waited=$((waited + 1))
  done
  echo $$ > "$LOCK/pid"
  trap 'rm -rf "$LOCK"' EXIT
}

acquire_lock

# ── 1. PHP 8.3+ ──────────────────────────────────────────────────────────────
command -v php >/dev/null || fail "PHP 8.3+ is required but php was not found on PATH"
php -r 'exit(PHP_VERSION_ID >= 80300 ? 0 : 1);' \
  || fail "PHP 8.3+ is required (found $(php -r 'echo PHP_VERSION;'))"

# ── 2. Copy app code when the plugin version changed ─────────────────────────
CODE_PATHS=(app bootstrap config routes resources public artisan composer.json composer.lock .env.example)
DB_CODE_PATHS=(migrations seeders factories)

synced=0
if [[ "$(cat "$APP/.plugin-root" 2>/dev/null || true)" != "$ROOT" ]]; then
  log "Syncing app code from $ROOT"
  mkdir -p "$APP/database"
  for p in "${CODE_PATHS[@]}"; do
    rm -rf "${APP:?}/$p"
    cp -R "$ROOT/$p" "$APP/$p"
  done
  for p in "${DB_CODE_PATHS[@]}"; do
    rm -rf "${APP:?}/database/$p"
    if [[ -e "$ROOT/database/$p" ]]; then cp -R "$ROOT/database/$p" "$APP/database/$p"; fi
  done
  # Stale discovery caches can stop artisan from booting after an update
  rm -f "$APP"/bootstrap/cache/*.php
  synced=1
fi

# Writable storage tree, kept across updates
[[ -d "$APP/storage" ]] || cp -R "$ROOT/storage" "$APP/storage"
mkdir -p "$APP/storage/logs" "$APP/storage/app/public" "$APP/storage/app/private" \
         "$APP/storage/framework/cache/data" "$APP/storage/framework/sessions" \
         "$APP/storage/framework/views" "$APP/bootstrap/cache"

# ── 3. Composer dependencies (only when composer.lock changed) ───────────────
find_composer() {
  if command -v composer >/dev/null; then
    COMPOSER=(composer)
    return
  fi
  local phar="$DATA/composer.phar"
  if [[ ! -f "$phar" ]]; then
    log "Composer not found, downloading a private copy to $phar"
    local expected actual
    expected="$(php -r 'echo trim(file_get_contents("https://composer.github.io/installer.sig"));')"
    php -r "copy('https://getcomposer.org/installer', '$DATA/composer-setup.php');"
    actual="$(php -r "echo hash_file('sha384', '$DATA/composer-setup.php');")"
    if [[ "$expected" != "$actual" ]]; then
      rm -f "$DATA/composer-setup.php"
      fail "Composer installer checksum mismatch"
    fi
    php "$DATA/composer-setup.php" --quiet --install-dir="$DATA" --filename=composer.phar
    rm -f "$DATA/composer-setup.php"
  fi
  COMPOSER=(php "$phar")
}

if [[ ! -f "$APP/vendor/autoload.php" ]] || ! cmp -s "$APP/composer.lock" "$APP/.composer.lock.installed"; then
  find_composer
  log "Installing PHP dependencies"
  "${COMPOSER[@]}" install --no-dev --no-interaction --no-scripts --no-progress \
    --prefer-dist --optimize-autoloader --working-dir="$APP" \
    || fail "composer install failed"
  cp "$APP/composer.lock" "$APP/.composer.lock.installed"
  rm -f "$APP"/bootstrap/cache/*.php
  synced=1
fi

# ── 4. .env ──────────────────────────────────────────────────────────────────
ENV_FILE="$APP/.env"
[[ -f "$ENV_FILE" ]] || cp "$APP/.env.example" "$ENV_FILE"
chmod 600 "$ENV_FILE"

set_env() {
  local key="$1" val="$2" tmp
  tmp="$(mktemp "$DATA/.env.XXXXXX")"
  awk -v k="$key" -v v="$val" '
    BEGIN { found = 0 }
    index($0, k "=") == 1 { print k "=" v; found = 1; next }
    { print }
    END { if (!found) print k "=" v }
  ' "$ENV_FILE" > "$tmp"
  cat "$tmp" > "$ENV_FILE"
  rm -f "$tmp"
}

# Only overwrite values we were given, so runs without credentials in the env
# (for example the /zoho-mcp:connect skill) keep what is already saved.
if [[ -n "$ZOHO_CLIENT_ID" ]]; then set_env ZOHO_CLIENT_ID "\"$ZOHO_CLIENT_ID\""; fi
if [[ -n "$ZOHO_CLIENT_SECRET" ]]; then set_env ZOHO_CLIENT_SECRET "\"$ZOHO_CLIENT_SECRET\""; fi

if [[ -n "$ZOHO_REGION" ]]; then
  case "$ZOHO_REGION" in
    com|eu|in|com.au) ;;
    *) fail "Unknown Zoho region '$ZOHO_REGION' (use com, eu, in or com.au)" ;;
  esac
  set_env ZOHO_ACCOUNTS_URL "https://accounts.zoho.$ZOHO_REGION"
  set_env ZOHO_SPRINTS_URL "https://sprintsapi.zoho.$ZOHO_REGION/zsapi"
fi

set_env APP_URL "http://localhost:8000"
set_env ZOHO_REDIRECT_URI "http://localhost:8000/zoho/callback"
# Never let Laravel write logs to stdout, it would corrupt the MCP stdio stream
set_env LOG_CHANNEL single

if grep -q '^APP_KEY=$' "$ENV_FILE"; then
  log "Generating app key"
  php "$APP/artisan" key:generate --force --no-interaction
fi

# ── 5. Database ──────────────────────────────────────────────────────────────
if [[ ! -f "$APP/database/database.sqlite" ]]; then
  touch "$APP/database/database.sqlite"
  synced=1
fi

if (( synced )); then
  log "Running migrations"
  php "$APP/artisan" migrate --force --no-interaction || fail "migrations failed"
  php "$APP/artisan" cache:clear --no-interaction || true
fi

echo "$ROOT" > "$APP/.plugin-root"
log "Setup OK"
