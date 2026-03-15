<?php

namespace App\Mcp\Tools;

use App\Services\ZohoSprintsService;
use PhpMcp\Server\Attributes\McpTool;

class EpicTools
{
    public function __construct(private ZohoSprintsService $sprints) {}

    #[McpTool(name: 'zoho_list_epics', description: 'List all epics in a Zoho Sprints project.')]
    public function listEpics(
        string $team_id,
        string $project_id,
    ): array {
        return $this->sprints->listEpics($team_id, $project_id);
    }

    #[McpTool(name: 'zoho_get_epic', description: 'Get details of a specific epic.')]
    public function getEpic(
        string $team_id,
        string $project_id,
        string $epic_id,
    ): array {
        return $this->sprints->getEpic($team_id, $project_id, $epic_id);
    }

    #[McpTool(name: 'zoho_create_epic', description: 'Create a new epic in a Zoho Sprints project.')]
    public function createEpic(
        string $team_id,
        string $project_id,
        string $name,
        ?string $description = null,
        ?string $color = null,
    ): array {
        $data = array_filter(compact('name', 'description', 'color'));

        return $this->sprints->createEpic($team_id, $project_id, $data);
    }

    #[McpTool(name: 'zoho_update_epic', description: 'Update an existing epic.')]
    public function updateEpic(
        string $team_id,
        string $project_id,
        string $epic_id,
        ?string $name = null,
        ?string $description = null,
        ?string $color = null,
    ): array {
        $data = array_filter(compact('name', 'description', 'color'));

        return $this->sprints->updateEpic($team_id, $project_id, $epic_id, $data);
    }

    #[McpTool(name: 'zoho_delete_epic', description: 'Delete an epic from a Zoho Sprints project.')]
    public function deleteEpic(
        string $team_id,
        string $project_id,
        string $epic_id,
    ): array {
        return $this->sprints->deleteEpic($team_id, $project_id, $epic_id);
    }
}
