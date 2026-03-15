<?php

namespace App\Mcp\Tools;

use App\Services\ZohoSprintsService;
use PhpMcp\Server\Attributes\McpTool;

class ProjectTools
{
    public function __construct(private ZohoSprintsService $sprints) {}

    #[McpTool(name: 'zoho_list_projects', description: 'List all projects in a Zoho Sprints team.')]
    public function listProjects(
        string $team_id,
    ): array {
        return $this->sprints->listProjects($team_id);
    }

    #[McpTool(name: 'zoho_get_project', description: 'Get details of a specific Zoho Sprints project.')]
    public function getProject(
        string $team_id,
        string $project_id,
    ): array {
        return $this->sprints->getProject($team_id, $project_id);
    }

    #[McpTool(name: 'zoho_create_project', description: 'Create a new project in a Zoho Sprints team.')]
    public function createProject(
        string $team_id,
        string $name,
        ?string $description = null,
    ): array {
        $data = array_filter(['name' => $name, 'description' => $description]);

        return $this->sprints->createProject($team_id, $data);
    }

    #[McpTool(name: 'zoho_update_project', description: 'Update an existing Zoho Sprints project.')]
    public function updateProject(
        string $team_id,
        string $project_id,
        ?string $name = null,
        ?string $description = null,
    ): array {
        $data = array_filter(compact('name', 'description'));

        return $this->sprints->updateProject($team_id, $project_id, $data);
    }

    #[McpTool(name: 'zoho_delete_project', description: 'Delete a Zoho Sprints project.')]
    public function deleteProject(
        string $team_id,
        string $project_id,
    ): array {
        return $this->sprints->deleteProject($team_id, $project_id);
    }

    #[McpTool(name: 'zoho_list_project_members', description: 'List all members of a Zoho Sprints project.')]
    public function listProjectMembers(
        string $team_id,
        string $project_id,
    ): array {
        return $this->sprints->listProjectMembers($team_id, $project_id);
    }
}
