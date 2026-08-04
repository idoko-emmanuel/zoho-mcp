<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\DecodesZohoRecords;
use App\Services\ZohoSprintsService;
use PhpMcp\Server\Attributes\McpTool;

class WorkflowTools
{
    use DecodesZohoRecords;

    public function __construct(private ZohoSprintsService $sprints) {}

    /**
     * @return array{statuses: list<array<string, mixed>>, workflowName: string}
     */
    #[McpTool(name: 'zoho_list_item_statuses', description: 'List a project\'s item statuses with their names and ids (e.g. "To do", "In progress", "Code Review", "QA", "Blocked", "Done"). Items only carry an opaque statusId — use this to resolve that id to a name, or to find the id to pass as the status argument of zoho_update_item.')]
    public function listItemStatuses(
        string $team_id,
        string $project_id,
    ): array {
        $raw = $this->sprints->listItemStatuses($team_id, $project_id);

        return [
            'statuses' => $this->decodeRecords($raw, 'statusJObj', 'status_prop', [
                'name' => 'statusName',
                'statusType' => 'statusType',
                'isDefault' => 'isDefault',
                'isStart' => 'isStart',
                'isEnd' => 'isEnd',
                'percentage' => 'statusPercentage',
                'color' => 'colorCode',
            ]),
            'workflowName' => (string) ($raw['workflowName'] ?? ''),
        ];
    }

    /**
     * @return array{itemTypes: list<array<string, mixed>>}
     */
    #[McpTool(name: 'zoho_list_item_types', description: 'List a project\'s item types with their names and ids (e.g. Task, Story, Bug, Epic, Feature). Resolves the projItemTypeId carried on an item.')]
    public function listItemTypes(
        string $team_id,
        string $project_id,
    ): array {
        $raw = $this->sprints->listItemTypes($team_id, $project_id);

        return [
            'itemTypes' => $this->decodeRecords($raw, 'projItemTypeJObj', 'projItemType_prop', [
                'name' => 'itemTypeName',
                'prefix' => 'prefix',
                'isDefault' => 'isDefault',
                'description' => 'itemTypeDescription',
            ]),
        ];
    }

    /**
     * @return array{priorities: list<array<string, mixed>>}
     */
    #[McpTool(name: 'zoho_list_priorities', description: 'List a project\'s priorities with their names and ids (e.g. High, Medium, Low, None). Resolves the projPriorityId carried on an item, and gives the value to pass as the priority argument of zoho_create_item / zoho_update_item.')]
    public function listPriorities(
        string $team_id,
        string $project_id,
    ): array {
        $raw = $this->sprints->listPriorities($team_id, $project_id);

        return [
            'priorities' => $this->decodeRecords($raw, 'projPriorityJObj', 'projPriority_prop', [
                'name' => 'priorityName',
                'isDefault' => 'isDefault',
                'color' => 'colorCode',
                'description' => 'priorityDescription',
            ]),
        ];
    }
}
