<?php

namespace App\Mcp\Tools;

use App\Services\ZohoSprintsService;
use PhpMcp\Server\Attributes\McpTool;

class CommentTools
{
    public function __construct(private ZohoSprintsService $sprints) {}

    #[McpTool(name: 'zoho_list_comments', description: 'List all comments on a Zoho Sprints item (task). Call zoho_list_modules first to get the module_id.')]
    public function listComments(
        string $team_id,
        string $project_id,
        string $module_id,
        string $item_id,
    ): array {
        return $this->sprints->listComments($team_id, $project_id, $module_id, $item_id);
    }

    #[McpTool(name: 'zoho_add_comment', description: 'Add a comment to a Zoho Sprints item (task). Call zoho_list_modules first to get the module_id.')]
    public function addComment(
        string $team_id,
        string $project_id,
        string $module_id,
        string $item_id,
        string $content,
    ): array {
        return $this->sprints->addComment($team_id, $project_id, $module_id, $item_id, $content);
    }

    #[McpTool(name: 'zoho_update_comment', description: 'Edit an existing comment on a Zoho Sprints item. Call zoho_list_modules first to get the module_id.')]
    public function updateComment(
        string $team_id,
        string $project_id,
        string $module_id,
        string $item_id,
        string $notes_id,
        string $content,
    ): array {
        return $this->sprints->updateComment($team_id, $project_id, $module_id, $item_id, $notes_id, $content);
    }

    #[McpTool(name: 'zoho_delete_comment', description: 'Delete a comment from a Zoho Sprints item. Call zoho_list_modules first to get the module_id.')]
    public function deleteComment(
        string $team_id,
        string $project_id,
        string $module_id,
        string $item_id,
        string $notes_id,
    ): array {
        return $this->sprints->deleteComment($team_id, $project_id, $module_id, $item_id, $notes_id);
    }
}
