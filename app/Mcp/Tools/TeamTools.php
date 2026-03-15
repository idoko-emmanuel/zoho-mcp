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

    #[McpTool(name: 'zoho_list_team_members', description: 'List all members of a Zoho Sprints team.')]
    public function listTeamMembers(
        string $team_id,
    ): array {
        return $this->sprints->listTeamMembers($team_id);
    }
}
