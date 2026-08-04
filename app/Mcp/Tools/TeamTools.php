<?php

namespace App\Mcp\Tools;

use App\Services\ZohoSprintsService;
use PhpMcp\Server\Attributes\McpTool;

class TeamTools
{
    public function __construct(private ZohoSprintsService $sprints) {}

    #[McpTool(name: 'zoho_list_teams', description: 'List all Zoho Sprints teams the authenticated user belongs to.')]
    public function listTeams(): array
    {
        return $this->sprints->listTeams();
    }

    #[McpTool(name: 'zoho_list_team_members', description: 'List all members of a Zoho Sprints team. Use index and range to paginate (default: first 100; index is 1-based).')]
    public function listTeamMembers(
        string $team_id,
        int $index = 1,
        int $range = 100,
    ): array {
        return $this->sprints->listTeamMembers($team_id, $index, $range);
    }
}
