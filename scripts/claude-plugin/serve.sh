#!/usr/bin/env bash
# zoho-mcp: MCP stdio entry point for the Claude Code plugin.
# Makes sure the app in CLAUDE_PLUGIN_DATA is set up, then hands stdio to Laravel.
set -euo pipefail

HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

bash "$HERE/setup.sh"

# Not authorised yet? Start the local auth server in the background so the
# link in the tools' "no token" error works straight away.
bash "$HERE/auth-server.sh" start </dev/null >/dev/null 2>&1 || true

# Lets the app restart the auth server later (see ZohoAuthService)
export ZOHO_MCP_PLUGIN_SCRIPTS="$HERE"

exec php "${CLAUDE_PLUGIN_DATA:?}/app/artisan" mcp:serve
