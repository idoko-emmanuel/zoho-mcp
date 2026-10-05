---
name: connect
description: Connect (or reconnect) the Zoho Sprints MCP server to a Zoho account by running the one-time OAuth flow. Use when the user asks to connect or authorise Zoho, when the automatic link from session start did not work, or when a zoho_* tool fails with "No Zoho token found" or an INVALID_OAUTHSCOPE error.
---

# Connect Zoho Sprints

The Zoho Sprints tools need a one-time OAuth authorisation. Tokens are saved in the plugin's
data directory and refresh automatically afterwards, so this only has to be done once (or again
after the user revokes access or the server gains new scopes).

Usually this happens without the command: when no token is saved, the plugin starts a local auth
server at session start and shows the user the link. This command is the fallback when that link
didn't work, or to re-authorise.

The app lives in `${CLAUDE_PLUGIN_DATA}/app`. Follow these steps in order.

## 1. Make sure the app is set up

Run:

```bash
CLAUDE_PLUGIN_ROOT="${CLAUDE_PLUGIN_ROOT}" CLAUDE_PLUGIN_DATA="${CLAUDE_PLUGIN_DATA}" bash "${CLAUDE_PLUGIN_ROOT}/scripts/claude-plugin/setup.sh"
```

If it fails, show the user the last 30 lines of `${CLAUDE_PLUGIN_DATA}/setup.log` and stop.

## 2. Check that credentials are configured

Run:

```bash
grep -E '^ZOHO_CLIENT_(ID|SECRET)=' "${CLAUDE_PLUGIN_DATA}/app/.env" | sed -E 's/=.+/=<set>/'
```

If either value is empty, tell the user to set the Client ID and Secret by running `/plugin`,
opening **zoho-mcp**, and choosing **Configure**, then to start a new session and run this
command again. Stop here.

## 3. Start the local auth server

To re-authorise an account that is already connected, first delete the saved token so the
server starts (skip this for a first-time connection):

```bash
php "${CLAUDE_PLUGIN_DATA}/app/artisan" tinker --execute='App\Models\ZohoToken::query()->delete();'
```

Then start the auth server in the background. It stops by itself once the token is saved:

```bash
CLAUDE_PLUGIN_DATA="${CLAUDE_PLUGIN_DATA}" bash "${CLAUDE_PLUGIN_ROOT}/scripts/claude-plugin/auth-server.sh" start
```

* `started` or `waiting`: the server is running on port 8000. Continue.
* `connected`: a token is already saved. Tell the user they're connected and stop.
* `port-busy`: something else is using port 8000, which Zoho redirects back to. Tell the user to
  free it and run this command again, then stop.

## 4. Have the user authorise

Tell the user to:

1. Open **http://localhost:8000/zoho/auth** in their browser.
2. Approve the permissions in Zoho.
3. Reply once the page shows "Zoho authorisation successful".

Wait for their reply.

## 5. Confirm and clean up

Check that a token was saved:

```bash
php "${CLAUDE_PLUGIN_DATA}/app/artisan" tinker --execute='echo App\Models\ZohoToken::count();'
```

A result of `1` means it worked: tell the user they're connected and can try something like
"List my Zoho Sprints teams" (the auth server stops by itself). If the count is `0`, show the end
of `${CLAUDE_PLUGIN_DATA}/auth-server.log` and `${CLAUDE_PLUGIN_DATA}/app/storage/logs/laravel.log`
and help them retry.
