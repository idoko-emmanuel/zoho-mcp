<?php

namespace App\Mcp\Tools;

use App\Services\ZohoSprintsService;
use PhpMcp\Server\Attributes\McpTool;

class TagTools
{
    public function __construct(private ZohoSprintsService $sprints) {}

    #[McpTool(name: 'zoho_get_item_tags', description: 'Fetch all tags associated with a Zoho Sprints item.')]
    public function getItemTags(
        string $team_id,
        string $project_id,
        string $sprint_id,
        string $item_id,
    ): array {
        return $this->sprints->getItemTags($team_id, $project_id, $sprint_id, $item_id);
    }

    #[McpTool(name: 'zoho_update_item_tags', description: 'Associate or update tags on a Zoho Sprints item. Provide a comma-separated list of tag IDs.')]
    public function updateItemTags(
        string $team_id,
        string $project_id,
        string $sprint_id,
        string $item_id,
        string $tag_ids,
    ): array {
        return $this->sprints->updateItemTags($team_id, $project_id, $sprint_id, $item_id, [
            'tagId' => $tag_ids,
        ]);
    }
}
