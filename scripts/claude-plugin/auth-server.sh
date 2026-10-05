#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
#  zoho-mcp: background Zoho OAuth server for the Claude Code plugin
#
#  auth-server.sh status   prints one of: connected, waiting, not-connected
#  auth-server.sh start    if no Zoho token is saved, starts `artisan serve` on
#                          127.0.0.1:8000 in the background so the user only has
#                          to open http://localhost:8000/zoho/auth. Prints
#                          connected, waiting, started or port-busy.
#
#  The background watcher stops the server as soon as a token is saved, or after
#  ZOHO_AUTH_TIMEOUT seconds (default 30 minutes), so port 8000 is not held.
#  Nothing here writes to the caller's stdout except the one status word.
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail

DATA="${CLAUDE_PLUGIN_DATA:?CLAUDE_PLUGIN_DATA is not set}"
APP="$DATA/app"
DB="$APP/database/database.sqlite"
PIDFILE="$DATA/auth-server.pid"
LOG="$DATA/auth-server.log"
HOST=127.0.0.1
PORT=8000
TIMEOUT="${ZOHO_AUTH_TIMEOUT:-1800}"

has_token() {
  php -r '
    try {
        $db = new PDO("sqlite:" . $argv[1]);
        exit((int) $db->query("SELECT COUNT(*) FROM zoho_tokens")->fetchColumn() > 0 ? 0 : 1);
    } catch (Throwable $e) {
        exit(1);
    }
  ' "$DB" 2>/dev/null
}

watcher_running() {
  local pid
  pid="$(cat "$PIDFILE" 2>/dev/null || true)"
  [[ -n "$pid" ]] && kill -0 "$pid" 2>/dev/null
}

port_in_use() {
  php -r '$s = @fsockopen($argv[1], (int) $argv[2], $n, $m, 0.5); exit($s ? 0 : 1);' "$HOST" "$PORT" 2>/dev/null
}

status() {
  if has_token; then echo connected
  elif watcher_running; then echo waiting
  else echo not-connected
  fi
}

SERVER_PID=""

stop_server() {
  if [[ -n "$SERVER_PID" ]]; then
    # artisan serve runs PHP's built-in server as a child process
    pkill -P "$SERVER_PID" 2>/dev/null || true
    kill "$SERVER_PID" 2>/dev/null || true
  fi
  rm -f "$PIDFILE"
}

watch() {
  echo $$ > "$PIDFILE"
  echo "[$(date '+%Y-%m-%d %H:%M:%S')] Starting auth server on http://localhost:$PORT"

  php "$APP/artisan" serve --host="$HOST" --port="$PORT" --no-interaction &
  SERVER_PID=$!
  trap stop_server EXIT

  local waited=0
  while (( waited < TIMEOUT )); do
    sleep 2
    waited=$((waited + 2))
    kill -0 "$SERVER_PID" 2>/dev/null || { echo "Auth server exited"; return; }
    if has_token; then
      # Give the callback page time to finish rendering before stopping
      sleep 3
      echo "[$(date '+%Y-%m-%d %H:%M:%S')] Token saved, stopping auth server"
      return
    fi
  done
  echo "[$(date '+%Y-%m-%d %H:%M:%S')] No authorisation after ${TIMEOUT}s, stopping auth server"
}

start() {
  if has_token; then echo connected; return; fi

  # The SessionStart hook and the MCP server both call this at session start
  local lock="$DATA/.auth-start.lock"
  if ! mkdir "$lock" 2>/dev/null; then
    # Another start is in progress, or a stale lock from a killed run
    if [[ -n "$(find "$lock" -maxdepth 0 -mmin +1 2>/dev/null)" ]]; then
      rm -rf "$lock"
      mkdir "$lock" 2>/dev/null || { echo waiting; return; }
    else
      echo waiting
      return
    fi
  fi
  trap 'rm -rf "$lock"' RETURN

  if watcher_running; then echo waiting; return; fi
  if port_in_use; then echo port-busy; return; fi

  # Fully detached: no inherited stdio, so the MCP stdio channel and the
  # SessionStart hook's output pipe are never held open by the watcher.
  if command -v setsid >/dev/null; then
    setsid bash "${BASH_SOURCE[0]}" _watch </dev/null >>"$LOG" 2>&1 &
  else
    nohup bash "${BASH_SOURCE[0]}" _watch </dev/null >>"$LOG" 2>&1 &
  fi

  # Wait briefly so the link works by the time the user clicks it
  for _ in $(seq 1 20); do
    port_in_use && break
    sleep 0.25
  done
  echo started
}

case "${1:-}" in
  status) status ;;
  start)  start ;;
  _watch) watch ;;
  *) echo "usage: $0 status|start" >&2; exit 64 ;;
esac
