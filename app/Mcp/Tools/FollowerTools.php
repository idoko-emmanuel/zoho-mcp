<?php

namespace App\Mcp\Tools;

use App\Services\ZohoSprintsService;
use PhpMcp\Server\Attributes\McpTool;

class FollowerTools
{
    public function __construct(private ZohoSprintsService $sprints) {}

    #[McpTool(name: 'zoho_get_item_followers', description: 'Fetch all users currently following a Zoho Sprints item.')]
    public function getItemFollowers(
        string $team_id,
        string $project_id,
        string $sprint_id,
        string $item_id,
    ): array {
        return $this->sprints->getItemFollowers($team_id, $project_id, $sprint_id, $item_id);
    }

    #[McpTool(name: 'zoho_update_item_followers', description: 'Add or remove followers on a Zoho Sprints item. action must be "add" or "remove". user_ids is a comma-separated list of user IDs.')]
    public function updateItemFollowers(
        string $team_id,
        string $project_id,
        string $sprint_id,
        string $item_id,
        string $action,
        string $user_ids,
    ): array {
        return $this->sprints->updateItemFollowers($team_id, $project_id, $sprint_id, $item_id, [
            'action'  => $action,
            'userIds' => $user_ids,
        ]);
    }
}
