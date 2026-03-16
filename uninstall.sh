#!/usr/bin/env bash
set -euo pipefail

# ─────────────────────────────────────────────
#  zoho-mcp — uninstaller
# ─────────────────────────────────────────────

INSTALL_DIR="$HOME/.local/share/zoho-mcp"
MCP_NAME="zoho-sprints"

# ── helpers ──────────────────────────────────

info()    { echo "  → $*"; }
success() { echo "  ✓ $*"; }

confirm() {
  local prompt="$1"
  read -r -p "  $prompt [y/N] " reply
  [[ "$reply" == [yY] ]]
}

# ── 1. Remove MCP registration ───────────────

if command -v claude &>/dev/null; then
  if claude mcp list 2>/dev/null | grep -q "$MCP_NAME"; then
    info "Removing MCP server '$MCP_NAME' from Claude Code ..."
    claude mcp remove "$MCP_NAME" --scope user 2>/dev/null || \
    claude mcp remove "$MCP_NAME" 2>/dev/null || true
    success "MCP server removed from Claude Code"
  else
    info "MCP server '$MCP_NAME' not registered in Claude Code — skipping"
  fi
else
  info "Claude Code CLI not found — skipping MCP deregistration"
  echo ""
  info "If you registered manually, remove the '$MCP_NAME' entry from:"
  echo "       ~/Library/Application Support/Claude/claude_desktop_config.json"
fi

# ── 2. Remove app directory ──────────────────

if [[ -d "$INSTALL_DIR" ]]; then
  info "Removing $INSTALL_DIR ..."
  rm -rf "$INSTALL_DIR"
  success "App directory removed"
else
  info "App directory not found at $INSTALL_DIR — skipping"
fi

# ── Done ─────────────────────────────────────

echo ""
echo "  Uninstall complete."
echo ""
echo "  Restart Claude Code (or Claude Desktop) to finish removing the server."
echo ""
