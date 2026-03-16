<?php

namespace App\Mcp\Tools;

use App\Services\ZohoSprintsService;
use PhpMcp\Server\Attributes\McpTool;

class ModuleTools
{
    public function __construct(private ZohoSprintsService $sprints) {}

    #[McpTool(name: 'zoho_list_modules', description: 'List all modules in a Zoho Sprints workspace. Required to get the module_id needed for comment operations (zoho_add_comment, zoho_update_comment, zoho_delete_comment, zoho_list_comments).')]
    public function listModules(
        string $team_id,
    ): array {
        return $this->sprints->listModules($team_id);
    }
}
