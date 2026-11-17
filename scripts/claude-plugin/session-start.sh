#!/usr/bin/env bash
# zoho-mcp: SessionStart hook for the Claude Code plugin.
#
# Sets the app up, and if Zoho isn't authorised yet, starts the local auth
# server and shows the user the link to click. Prints nothing once connected.
set -euo pipefail

HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ENV_FILE="${CLAUDE_PLUGIN_DATA:?}/app/.env"
AUTH_URL="http://localhost:8000/zoho/auth"

bash "$HERE/setup.sh"

say() {
  # $1 = message shown to the user, $2 = context for Claude
  php -r 'echo json_encode([
      "systemMessage" => $argv[1],
      "hookSpecificOutput" => ["hookEventName" => "SessionStart", "additionalContext" => $argv[2]],
  ], JSON_UNESCAPED_SLASHES), PHP_EOL;' "$1" "$2"
}

CLOUD=0
[[ "${CLAUDE_CODE_REMOTE:-}" == "true" ]] && CLOUD=1

if ! grep -Eq '^ZOHO_CLIENT_ID=.+' "$ENV_FILE" || ! grep -Eq '^ZOHO_CLIENT_SECRET=.+' "$ENV_FILE"; then
  if (( CLOUD )); then
    say "Zoho Sprints: no Zoho Client ID or Secret in this cloud session. An admin adds ZOHO_CLIENT_ID and ZOHO_CLIENT_SECRET to the cloud environment's variables." \
        "The zoho-mcp plugin has no Zoho Client ID or Secret, so its tools will fail. In a cloud session they come from the environment variables ZOHO_CLIENT_ID and ZOHO_CLIENT_SECRET, which an admin sets on the cloud environment. If the user asks about Zoho Sprints, tell them that."
  else
    say "Zoho Sprints: add your Zoho Client ID and Secret (run /plugin, open zoho-mcp, choose Configure), then start a new session." \
        "The zoho-mcp plugin has no Zoho Client ID or Secret configured, so its tools will fail. If the user asks about Zoho Sprints, tell them to run /plugin, open zoho-mcp, choose Configure, then start a new session."
  fi
  exit 0
fi

# Cloud session: the browser cannot reach localhost here, so don't start the
# local auth server. /zoho-mcp:connect has the user paste the callback URL back.
if (( CLOUD )); then
  if [[ "$(bash "$HERE/auth-server.sh" status 2>/dev/null || true)" != "connected" ]]; then
    say "Zoho Sprints isn't connected in this cloud session. Run /zoho-mcp:connect when you need it." \
        "The zoho-mcp plugin is not authorised with Zoho in this cloud session (tokens do not carry over between cloud sessions). If the user asks about Zoho Sprints, or a zoho tool says no token was found, run the /zoho-mcp:connect skill: it gives them a link, they approve in Zoho, then paste back the URL their browser was sent to."
  fi
  exit 0
fi

case "$(bash "$HERE/auth-server.sh" start 2>/dev/null || echo error)" in
  connected) ;;
  started|waiting)
    say "Zoho Sprints isn't connected yet. Open $AUTH_URL to authorise it (one time only)." \
        "The zoho-mcp plugin is not authorised with Zoho yet. A local auth server is running. If the user asks about Zoho Sprints, or a zoho tool says no token was found, give them this link to open: $AUTH_URL . Once they approve in Zoho, the tools work without restarting. If the link does not load, suggest /zoho-mcp:connect."
    ;;
  port-busy)
    say "Zoho Sprints isn't connected yet, but port 8000 is in use. Free port 8000, then run /zoho-mcp:connect." \
        "The zoho-mcp plugin is not authorised with Zoho, and port 8000 (needed for the OAuth callback) is busy. If the user asks about Zoho Sprints, tell them to free port 8000 and run /zoho-mcp:connect."
    ;;
  *)
    say "Zoho Sprints isn't connected yet. Run /zoho-mcp:connect to authorise it." \
        "The zoho-mcp plugin is not authorised with Zoho. If the user asks about Zoho Sprints, suggest /zoho-mcp:connect."
    ;;
esac
