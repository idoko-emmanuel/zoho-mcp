# Zoho Sprints MCP Server

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

- PHP 8.3+
- Composer
- A [Zoho Sprints](https://www.zoho.com/sprints/) account with **API access enabled**
- A Zoho OAuth app registered at [api-console.zoho.com](https://api-console.zoho.com/) → **Server-based Applications**

---

## Installation

```bash
git clone https://github.com/idoko-emmanuel/zoho-mcp.git
cd zoho-mcp
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

## Configuration

Edit `.env` and fill in your Zoho credentials:

```env
ZOHO_CLIENT_ID=your-client-id
ZOHO_CLIENT_SECRET=your-client-secret
ZOHO_REDIRECT_URI=http://localhost:8000/zoho/callback

# Change to .eu / .in / .com.au if your Zoho account is in another region
ZOHO_ACCOUNTS_URL=https://accounts.zoho.com
ZOHO_SPRINTS_URL=https://sprintsapi.zoho.com/zsapi
```

**Enable API access in Zoho Sprints:**
Go to **Zoho Sprints → Settings → API** and enable API access for your team.

---

## Authorisation (One-time)

Start the server and visit the auth URL in your browser:

```bash
php artisan serve
```

Then open: [http://localhost:8000/zoho/auth](http://localhost:8000/zoho/auth)

Zoho will ask you to grant permissions. After approval you'll be redirected back and the tokens are saved. The server handles silent token refresh automatically from that point on.

---

## Connecting to Claude

### Claude Code (recommended)

Add the server at user scope so it's available in every session:

```bash
claude mcp add --scope user zoho-sprints -- php /path/to/zoho-mcp/artisan mcp:serve
```

### Claude Desktop

Add to `~/Library/Application Support/Claude/claude_desktop_config.json`:

```json
{
  "mcpServers": {
    "zoho-sprints": {
      "command": "php",
      "args": ["/path/to/zoho-mcp/artisan", "mcp:serve"]
    }
  }
}
```

Restart Claude Desktop after saving.

---

## Usage Examples

Once connected, you can ask Claude things like:

- *"List all my Zoho Sprints projects"*
- *"Create a new sprint called 'Sprint 5' in project X starting next Monday"*
- *"Show me all open items in the current sprint"*
- *"Add a comment to task #123: 'Ready for review'"*
- *"Move item X to done and assign it to John"*

---

## Running Tests

```bash
php artisan test
```

---

## Project Structure

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

## License

MIT
