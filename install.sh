#!/usr/bin/env bash
set -euo pipefail

# ─────────────────────────────────────────────
#  zoho-mcp — one-time installer
# ─────────────────────────────────────────────

INSTALL_DIR="$HOME/.local/share/zoho-mcp"
REPO_URL="https://github.com/idoko-emmanuel/zoho-mcp.git"
MCP_NAME="zoho-sprints"

# ── helpers ──────────────────────────────────

info()    { echo "  → $*"; }
success() { echo "  ✓ $*"; }
fail()    { echo "  ✗ $*" >&2; exit 1; }

require_cmd() {
  command -v "$1" &>/dev/null || fail "$1 is required but not found. $2"
}

# ── 1. Check Git ──────────────────────────────

require_cmd git "Install it from https://git-scm.com"

# ── 2. Check PHP 8.3+ ────────────────────────

require_cmd php "Install it from https://php.net/downloads or via your package manager."

PHP_VERSION=$(php -r 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;')
PHP_MAJOR=$(php -r 'echo PHP_MAJOR_VERSION;')
PHP_MINOR=$(php -r 'echo PHP_MINOR_VERSION;')

if [[ "$PHP_MAJOR" -lt 8 || ( "$PHP_MAJOR" -eq 8 && "$PHP_MINOR" -lt 3 ) ]]; then
  fail "PHP 8.3+ is required (found $PHP_VERSION). See https://php.net/downloads"
fi

success "PHP $PHP_VERSION"

# ── 3. Ensure Composer ───────────────────────

if command -v composer &>/dev/null; then
  success "Composer $(composer --version --no-ansi 2>/dev/null | awk '{print $3}')"
else
  info "Composer not found — installing to /usr/local/bin/composer ..."

  EXPECTED_CHECKSUM="$(php -r 'copy("https://composer.github.io/installer.sig", "php://stdout");')"
  php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
  ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"

  if [[ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]]; then
    rm -f composer-setup.php
    fail "Composer installer checksum mismatch — aborting for security."
  fi

  php composer-setup.php --quiet
  rm -f composer-setup.php

  if [[ -w /usr/local/bin ]]; then
    mv composer.phar /usr/local/bin/composer
  else
    sudo mv composer.phar /usr/local/bin/composer
  fi

  success "Composer installed"
fi

# ── 4. Clone or update the repo ──────────────

if [[ -d "$INSTALL_DIR/.git" ]]; then
  info "Repo already exists at $INSTALL_DIR — pulling latest ..."
  git -C "$INSTALL_DIR" pull --ff-only
else
  info "Cloning into $INSTALL_DIR ..."
  git clone "$REPO_URL" "$INSTALL_DIR"
fi

success "Repo ready at $INSTALL_DIR"

# ── 5. Configure environment ─────────────────

cp "$INSTALL_DIR/.env.example" "$INSTALL_DIR/.env"

# ── 6. Install PHP dependencies ──────────────

info "Installing dependencies ..."
composer install --no-dev --no-interaction --no-scripts --working-dir="$INSTALL_DIR"
php "$INSTALL_DIR/artisan" package:discover --ansi
success "Dependencies installed"

php "$INSTALL_DIR/artisan" key:generate --force
success "App key set"

# ── 7. Run database migrations ───────────────

info "Running migrations ..."
php "$INSTALL_DIR/artisan" migrate --force
success "Database ready"

# ── 8. Collect Zoho credentials ──────────────

echo ""
echo "  ┌─────────────────────────────────────────────┐"
echo "  │          Zoho OAuth Configuration           │"
echo "  │                                             │"
echo "  │  Register your app at:                      │"
echo "  │  https://api-console.zoho.com/              │"
echo "  │  → Server-based Applications               │"
echo "  └─────────────────────────────────────────────┘"
echo ""

ZOHO_CLIENT_ID=""
ZOHO_CLIENT_SECRET=""

read -r -p "  Zoho Client ID:     " ZOHO_CLIENT_ID </dev/tty
read -r -p "  Zoho Client Secret: " ZOHO_CLIENT_SECRET </dev/tty
echo ""

# Region selection
echo "  Zoho data centre region:"
echo "    1) .com  — US (default)"
echo "    2) .eu   — Europe"
echo "    3) .in   — India"
echo "    4) .com.au — Australia"
echo ""
REGION_CHOICE=""
read -r -p "  Select region [1]: " REGION_CHOICE </dev/tty
REGION_CHOICE="${REGION_CHOICE:-1}"

case "$REGION_CHOICE" in
  2) ZOHO_ACCOUNTS_URL="https://accounts.zoho.eu"
     ZOHO_SPRINTS_URL="https://sprintsapi.zoho.eu/zsapi" ;;
  3) ZOHO_ACCOUNTS_URL="https://accounts.zoho.in"
     ZOHO_SPRINTS_URL="https://sprintsapi.zoho.in/zsapi" ;;
  4) ZOHO_ACCOUNTS_URL="https://accounts.zoho.com.au"
     ZOHO_SPRINTS_URL="https://sprintsapi.zoho.com.au/zsapi" ;;
  *) ZOHO_ACCOUNTS_URL="https://accounts.zoho.com"
     ZOHO_SPRINTS_URL="https://sprintsapi.zoho.com/zsapi" ;;
esac

# Write credentials into .env
ENV_FILE="$INSTALL_DIR/.env"

set_env() {
  local key="$1" val="$2"
  if grep -q "^${key}=" "$ENV_FILE" 2>/dev/null; then
    # Replace existing line
    local tmp
    tmp=$(mktemp)
    awk -v k="$key" -v v="$val" 'BEGIN{found=0} $0 ~ "^"k"=" {print k"="v; found=1; next} {print} END{if(!found) print k"="v}' "$ENV_FILE" > "$tmp"
    mv "$tmp" "$ENV_FILE"
  else
    printf '\n%s=%s\n' "$key" "$val" >> "$ENV_FILE"
  fi
}

set_env "ZOHO_CLIENT_ID"     "\"$ZOHO_CLIENT_ID\""
set_env "ZOHO_CLIENT_SECRET" "\"$ZOHO_CLIENT_SECRET\""
set_env "ZOHO_ACCOUNTS_URL"  "$ZOHO_ACCOUNTS_URL"
set_env "ZOHO_SPRINTS_URL"   "$ZOHO_SPRINTS_URL"

success "Zoho credentials saved"

# ── 9. Register with Claude Code ─────────────

if command -v claude &>/dev/null; then
  info "Registering MCP server with Claude Code ..."
  claude mcp add --scope user "$MCP_NAME" -- php "$INSTALL_DIR/artisan" mcp:serve
  success "MCP server registered as '$MCP_NAME'"
else
  echo ""
  info "Claude Code CLI not found. Register manually:"
  echo ""
  echo "    claude mcp add --scope user $MCP_NAME -- php $INSTALL_DIR/artisan mcp:serve"
  echo ""
  echo "  Or for Claude Desktop, add to:"
  echo "    ~/Library/Application Support/Claude/claude_desktop_config.json"
  echo ""
  echo '    {'
  echo '      "mcpServers": {'
  echo "        \"$MCP_NAME\": {"
  echo '          "command": "php",'
  echo "          \"args\": [\"$INSTALL_DIR/artisan\", \"mcp:serve\"]"
  echo '        }'
  echo '      }'
  echo '    }'
fi

# ── Done ─────────────────────────────────────

echo ""
echo "  Installation complete."
echo ""
echo "  One more step — complete the Zoho OAuth flow:"
echo ""
echo "    1. Start the server:"
echo "         php $INSTALL_DIR/artisan serve"
echo ""
echo "    2. Open in your browser:"
echo "         http://localhost:8000/zoho/auth"
echo ""
echo "    3. Approve access in Zoho. Tokens are saved automatically."
echo "       You can stop the server once authorised."
echo ""
echo "  Then start a new Claude Code session and try:"
echo "    \"List all my Zoho Sprints teams\""
echo ""
