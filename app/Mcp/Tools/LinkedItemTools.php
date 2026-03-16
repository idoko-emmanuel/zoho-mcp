<?php

namespace App\Mcp\Tools;

use App\Services\ZohoSprintsService;
use PhpMcp\Server\Attributes\McpTool;

class LinkedItemTools
{
    public function __construct(private ZohoSprintsService $sprints) {}

    #[McpTool(name: 'zoho_get_linked_items', description: 'Fetch all items linked to a specific Zoho Sprints item.')]
    public function getLinkedItems(
        string $team_id,
        string $project_id,
        string $sprint_id,
        string $item_id,
    ): array {
        return $this->sprints->getLinkedItems($team_id, $project_id, $sprint_id, $item_id);
    }

    #[McpTool(name: 'zoho_link_items', description: 'Link two Zoho Sprints work items together using a link type.')]
    public function linkItems(
        string $team_id,
        string $project_id,
        string $sprint_id,
        string $item_id,
        string $link_type_id,
        string $linked_item_id,
    ): array {
        return $this->sprints->linkItems($team_id, $project_id, $sprint_id, $item_id, [
            'linkTypeId'    => $link_type_id,
            'linkedItemId'  => $linked_item_id,
        ]);
    }
}
