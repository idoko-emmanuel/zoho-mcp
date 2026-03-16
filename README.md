# Zoho Sprints MCP Server

[![PHP Version](https://img.shields.io/badge/php-8.3%2B-blue)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/laravel-12.x-red)](https://laravel.com)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Install](https://img.shields.io/badge/Install-curl%20%7C%20bash-brightgreen)](#installation)

A Laravel 12 MCP server that connects Claude AI to Zoho Sprints — manage teams, projects, sprints, tasks, epics, and comments directly from your Claude conversations.

Built with [php-mcp/laravel](https://github.com/php-mcp/laravel) and the [Model Context Protocol](https://modelcontextprotocol.io/).

---

## Features

- **27 MCP tools** covering the full Zoho Sprints API
- Full CRUD for **projects, sprints, items (tasks), epics, and comments**
- Read access to **teams and members**
- Automatic **OAuth 2.0 token management** with silent refresh
- Works with **Claude Code** (stdio) and **Claude Desktop** (HTTP)

## Tools

| Category | Tools |
| --- | --- |
| Teams | `zoho_list_teams`, `zoho_list_team_members` |
| Projects | `zoho_list_projects`, `zoho_get_project`, `zoho_create_project`, `zoho_update_project`, `zoho_delete_project`, `zoho_list_project_members` |
| Sprints | `zoho_list_sprints`, `zoho_get_sprint`, `zoho_create_sprint`, `zoho_update_sprint`, `zoho_delete_sprint` |
| Items | `zoho_list_items`, `zoho_get_item`, `zoho_create_item`, `zoho_update_item`, `zoho_delete_item` |
| Epics | `zoho_list_epics`, `zoho_get_epic`, `zoho_create_epic`, `zoho_update_epic`, `zoho_delete_epic` |
| Comments | `zoho_list_comments`, `zoho_add_comment`, `zoho_update_comment`, `zoho_delete_comment` |

---

## Requirements

- PHP 8.3+ — [php.net/downloads](https://php.net/downloads) or via your package manager
- Composer — [getcomposer.org/download](https://getcomposer.org/download) (the install script handles this automatically)
- Git
- A [Zoho Sprints](https://www.zoho.com/sprints/) account with **API access enabled**
- A Zoho OAuth app registered at [api-console.zoho.com](https://api-console.zoho.com/) → **Server-based Applications**

---

## Installation

### One-line install (recommended)

The install script checks for PHP 8.3+, installs Composer if missing, clones the repo, sets up the database, prompts for your Zoho credentials, and registers the MCP server with Claude Code automatically.

```bash
curl -fsSL https://raw.githubusercontent.com/idoko-emmanuel/zoho-mcp/main/install.sh | bash
```

After the script completes, follow the on-screen instructions to complete the OAuth flow (one-time browser step).

### Manual install

If you prefer to run each step yourself:

#### 1. Install Composer (skip if already installed)

```bash
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php && rm composer-setup.php
sudo mv composer.phar /usr/local/bin/composer
```

#### 2. Clone and set up

```bash
git clone https://github.com/idoko-emmanuel/zoho-mcp.git ~/.local/share/zoho-mcp
cd ~/.local/share/zoho-mcp
composer install --no-dev
cp .env.example .env
php artisan key:generate
php artisan migrate
```

#### 3. Configure Zoho credentials

Edit `.env` and fill in your Zoho OAuth credentials:

```env
ZOHO_CLIENT_ID=your-client-id
ZOHO_CLIENT_SECRET=your-client-secret
ZOHO_REDIRECT_URI=http://localhost:8000/zoho/callback

# Change to .eu / .in / .com.au if your Zoho account is in another region
ZOHO_ACCOUNTS_URL=https://accounts.zoho.com
ZOHO_SPRINTS_URL=https://sprintsapi.zoho.com/zsapi
```

#### 4. Register with Claude Code

```bash
claude mcp add --scope user zoho-sprints -- php ~/.local/share/zoho-mcp/artisan mcp:serve
```

Or for Claude Desktop, add to `~/Library/Application Support/Claude/claude_desktop_config.json`:

```json
{
  "mcpServers": {
    "zoho-sprints": {
      "command": "php",
      "args": ["/Users/you/.local/share/zoho-mcp/artisan", "mcp:serve"]
    }
  }
}
```

---

## Uninstallation

```bash
curl -fsSL https://raw.githubusercontent.com/idoko-emmanuel/zoho-mcp/main/uninstall.sh | bash
```

This will:

- Remove the MCP server registration from Claude Code
- Remove `~/.local/share/zoho-mcp`

---

## Zoho OAuth setup

### 1. Register your OAuth app

Go to [api-console.zoho.com](https://api-console.zoho.com/) and create a **Server-based Application**. Set the redirect URI to:

```text
http://localhost:8000/zoho/callback
```

Copy your **Client ID** and **Client Secret** — you'll need them during install (or to edit `.env` manually).

### 2. Enable API access in Zoho Sprints

Go to **Zoho Sprints → Settings → API** and enable API access for your team.

### 3. Authorise (one-time)

Start the local server and complete the OAuth flow in your browser:

```bash
php ~/.local/share/zoho-mcp/artisan serve
```

Then open: [http://localhost:8000/zoho/auth](http://localhost:8000/zoho/auth)

Zoho will ask you to grant permissions. After approval you'll be redirected back and the tokens are saved automatically. You can stop the server once authorised — the MCP server runs via stdio and doesn't need a persistent HTTP server.

---

## Usage examples

Once connected, start a new Claude Code session and try:

- *"List all my Zoho Sprints teams"*
- *"Show me all projects in my team"*
- *"Create a new sprint called 'Sprint 5' in project X starting next Monday"*
- *"Show me all open items in the current sprint"*
- *"Add a comment to task #123: 'Ready for review'"*
- *"Move item X to done and assign it to John"*

---

## Running tests

```bash
php artisan test
```

---

## Project structure

```text
app/
├── Http/Controllers/
│   └── ZohoAuthController.php   # OAuth redirect + callback
├── Mcp/Tools/
│   ├── TeamTools.php            # Team & member tools
│   ├── ProjectTools.php         # Project CRUD tools
│   ├── SprintTools.php          # Sprint CRUD tools
│   ├── ItemTools.php            # Task/item CRUD tools
│   ├── EpicTools.php            # Epic CRUD tools
│   └── CommentTools.php         # Comment CRUD tools
├── Models/
│   └── ZohoToken.php            # OAuth token model
└── Services/
    ├── ZohoAuthService.php      # OAuth flow & token refresh
    └── ZohoSprintsService.php   # Zoho Sprints HTTP client
config/
├── mcp.php                      # MCP server configuration
└── zoho.php                     # Zoho API configuration
```

---

## Contributing

1. Create a feature branch from `main`:

    ```bash
    git checkout main
    git pull
    git checkout -b feat/your-feature-name
    ```

2. Make your changes and commit them.

3. Push your branch and open a pull request against `main`:

    ```bash
    git push -u origin feat/your-feature-name
    ```

4. Ensure CI checks pass before requesting a review.

---

## License

MIT
