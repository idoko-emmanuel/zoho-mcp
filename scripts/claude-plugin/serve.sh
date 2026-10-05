#!/usr/bin/env bash
# zoho-mcp: MCP stdio entry point for the Claude Code plugin.
# Makes sure the app in CLAUDE_PLUGIN_DATA is set up, then hands stdio to Laravel.
set -euo pipefail

HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

bash "$HERE/setup.sh"

exec php "${CLAUDE_PLUGIN_DATA:?}/app/artisan" mcp:serve
