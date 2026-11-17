#!/usr/bin/env bash
# zoho-mcp: MCP stdio entry point for the Claude Code plugin.
# Makes sure the app in CLAUDE_PLUGIN_DATA is set up, then hands stdio to Laravel.
set -euo pipefail

HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

bash "$HERE/setup.sh"

# An unconfigured userConfig option reaches this process empty or as an
# unexpanded "${user_config...}". Laravel lets a real environment variable win
# over .env even when it is empty, so drop those and let the saved .env apply.
for var in ZOHO_CLIENT_ID ZOHO_CLIENT_SECRET ZOHO_REGION; do
  val="${!var:-}"
  if [[ -z "$val" || "$val" == '${'* ]]; then unset "$var"; fi
done

# Not authorised yet? Start the local auth server in the background so the
# link in the tools' "no token" error works straight away. Not in a cloud
# session: the browser cannot reach localhost there (see /zoho-mcp:connect).
if [[ "${CLAUDE_CODE_REMOTE:-}" != "true" ]]; then
  bash "$HERE/auth-server.sh" start </dev/null >/dev/null 2>&1 || true
fi

# Lets the app restart the auth server later (see ZohoAuthService)
export ZOHO_MCP_PLUGIN_SCRIPTS="$HERE"

exec php "${CLAUDE_PLUGIN_DATA:?}/app/artisan" mcp:serve
