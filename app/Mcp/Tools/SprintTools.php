<?php

namespace App\Mcp\Tools;

use App\Services\ZohoSprintsService;
use PhpMcp\Server\Attributes\McpTool;

class SprintTools
{
    public function __construct(private ZohoSprintsService $sprints) {}

    #[McpTool(name: 'zoho_list_sprints', description: 'List all sprints in a Zoho Sprints project.')]
    public function listSprints(
        string $team_id,
        string $project_id,
    ): array {
        return $this->sprints->listSprints($team_id, $project_id);
    }

    #[McpTool(name: 'zoho_get_sprint', description: 'Get details of a specific sprint.')]
    public function getSprint(
        string $team_id,
        string $project_id,
        string $sprint_id,
    ): array {
        return $this->sprints->getSprint($team_id, $project_id, $sprint_id);
    }

    #[McpTool(name: 'zoho_create_sprint', description: 'Create a new sprint in a Zoho Sprints project.')]
    public function createSprint(
        string $team_id,
        string $project_id,
        string $name,
        ?string $start_date = null,
        ?string $end_date = null,
    ): array {
        $data = array_filter([
            'name'       => $name,
            'startdate'  => $start_date,
            'enddate'    => $end_date,
        ]);

        return $this->sprints->createSprint($team_id, $project_id, $data);
    }

    #[McpTool(name: 'zoho_update_sprint', description: 'Update an existing sprint (name, dates, or status).')]
    public function updateSprint(
        string $team_id,
        string $project_id,
        string $sprint_id,
        ?string $name = null,
        ?string $start_date = null,
        ?string $end_date = null,
        ?string $status = null,
    ): array {
        $data = array_filter([
            'name'       => $name,
            'startdate'  => $start_date,
            'enddate'    => $end_date,
            'status'     => $status,
        ]);

        return $this->sprints->updateSprint($team_id, $project_id, $sprint_id, $data);
    }

    #[McpTool(name: 'zoho_delete_sprint', description: 'Delete a sprint from a Zoho Sprints project.')]
    public function deleteSprint(
        string $team_id,
        string $project_id,
        string $sprint_id,
    ): array {
        return $this->sprints->deleteSprint($team_id, $project_id, $sprint_id);
    }
}
