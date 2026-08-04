<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\DecodesZohoRecords;
use App\Services\ZohoSprintsService;
use PhpMcp\Server\Attributes\McpTool;

class ItemTools
{
    use DecodesZohoRecords;

    public function __construct(private ZohoSprintsService $sprints) {}

    #[McpTool(name: 'zoho_list_items', description: 'List all items (tasks) in a sprint. Pass compact=true to get decoded, named fields WITHOUT each item\'s full HTML description — far smaller, and enough to triage or filter a sprint by owner, status, type or priority. Fetch the description for the one item you care about with zoho_get_item.')]
    public function listItems(
        string $team_id,
        string $project_id,
        string $sprint_id,
        bool $compact = false,
    ): array {
        $raw = $this->sprints->listItems($team_id, $project_id, $sprint_id);

        return $compact ? $this->compactItems($raw) : $raw;
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array{items: list<array<string, mixed>>, count: int}
     */
    private function compactItems(array $raw): array
    {
        $names = $raw['userDisplayName'] ?? [];

        $items = $this->decodeRecords($raw, 'itemJObj', 'item_prop', [
            'itemNo' => 'itemNo',
            'name' => 'itemName',
            'statusId' => 'statusId',
            'itemTypeId' => 'projItemTypeId',
            'priorityId' => 'projPriorityId',
            'ownerIds' => 'ownerId',
            'sprintId' => 'sprintId',
            'epicId' => 'epicId',
            'parentItem' => 'parentItem',
            'depth' => 'depth',
            'sequence' => 'sequence',
            'points' => 'points',
            'startDate' => 'startDate',
            'endDate' => 'endDate',
            'hasComments' => 'isNotesAdded',
        ]);

        foreach ($items as $index => $item) {
            $ownerIds = (array) ($item['ownerIds'] ?? []);
            $items[$index]['ownerIds'] = $ownerIds;
            $items[$index]['owners'] = array_values(array_map(
                fn ($ownerId) => $names[$ownerId] ?? $ownerId,
                $ownerIds,
            ));
        }

        return ['items' => $items, 'count' => count($items)];
    }

    #[McpTool(name: 'zoho_get_item', description: 'Get full details of a specific item (task) in a sprint.')]
    public function getItem(
        string $team_id,
        string $project_id,
        string $sprint_id,
        string $item_id,
    ): array {
        return $this->sprints->getItem($team_id, $project_id, $sprint_id, $item_id);
    }

    #[McpTool(name: 'zoho_create_item', description: 'Create a new item (task) inside a sprint.')]
    public function createItem(
        string $team_id,
        string $project_id,
        string $sprint_id,
        string $name,
        ?string $description = null,
        ?string $assignee = null,
        ?string $priority = null,
        ?string $due_date = null,
        ?string $epic_id = null,
    ): array {
        $data = array_filter([
            'name' => $name,
            'description' => $description,
            'assignee' => $assignee,
            'priority' => $priority,
            'duedate' => $due_date,
            'epic' => $epic_id,
        ]);

        return $this->sprints->createItem($team_id, $project_id, $sprint_id, $data);
    }

    #[McpTool(name: 'zoho_update_item', description: 'Update an existing item (task) — name, description, assignee, priority, status, due date, or epic.')]
    public function updateItem(
        string $team_id,
        string $project_id,
        string $sprint_id,
        string $item_id,
        ?string $name = null,
        ?string $description = null,
        ?string $assignee = null,
        ?string $priority = null,
        ?string $status = null,
        ?string $due_date = null,
        ?string $epic_id = null,
    ): array {
        $data = array_filter([
            'name' => $name,
            'description' => $description,
            'assignee' => $assignee,
            'priority' => $priority,
            'status' => $status,
            'duedate' => $due_date,
            'epic' => $epic_id,
        ]);

        return $this->sprints->updateItem($team_id, $project_id, $sprint_id, $item_id, $data);
    }

    #[McpTool(name: 'zoho_delete_item', description: 'Delete an item (task) from a sprint.')]
    public function deleteItem(
        string $team_id,
        string $project_id,
        string $sprint_id,
        string $item_id,
    ): array {
        return $this->sprints->deleteItem($team_id, $project_id, $sprint_id, $item_id);
    }

    #[McpTool(name: 'zoho_create_subitem', description: 'Create a sub-item under an existing item in a sprint.')]
    public function createSubitem(
        string $team_id,
        string $project_id,
        string $sprint_id,
        string $item_id,
        string $name,
        ?string $description = null,
        ?string $assignee = null,
        ?string $priority = null,
        ?string $due_date = null,
    ): array {
        $data = array_filter([
            'name' => $name,
            'description' => $description,
            'assignee' => $assignee,
            'priority' => $priority,
            'duedate' => $due_date,
        ]);

        return $this->sprints->createSubitem($team_id, $project_id, $sprint_id, $item_id, $data);
    }
}
