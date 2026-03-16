<?php

namespace App\Mcp\Tools;

use App\Services\ZohoSprintsService;
use PhpMcp\Server\Attributes\McpTool;

class ReminderTools
{
    public function __construct(private ZohoSprintsService $sprints) {}

    #[McpTool(name: 'zoho_get_item_reminder', description: 'Fetch the reminder set for a Zoho Sprints item.')]
    public function getItemReminder(
        string $team_id,
        string $project_id,
        string $sprint_id,
        string $item_id,
    ): array {
        return $this->sprints->getItemReminder($team_id, $project_id, $sprint_id, $item_id);
    }

    #[McpTool(name: 'zoho_add_item_reminder', description: 'Set a reminder for a Zoho Sprints item. remind_time format: epoch milliseconds.')]
    public function addItemReminder(
        string $team_id,
        string $project_id,
        string $sprint_id,
        string $item_id,
        string $remind_time,
        ?string $note = null,
    ): array {
        $data = array_filter([
            'remindTime' => $remind_time,
            'note'       => $note,
        ]);

        return $this->sprints->addItemReminder($team_id, $project_id, $sprint_id, $item_id, $data);
    }

    #[McpTool(name: 'zoho_update_item_reminder', description: 'Update an existing reminder on a Zoho Sprints item. remind_time format: epoch milliseconds.')]
    public function updateItemReminder(
        string $team_id,
        string $project_id,
        string $sprint_id,
        string $item_id,
        string $reminder_id,
        string $remind_time,
        ?string $note = null,
    ): array {
        $data = array_filter([
            'remindTime' => $remind_time,
            'note'       => $note,
        ]);

        return $this->sprints->updateItemReminder($team_id, $project_id, $sprint_id, $item_id, $reminder_id, $data);
    }

    #[McpTool(name: 'zoho_delete_item_reminder', description: 'Delete a reminder from a Zoho Sprints item.')]
    public function deleteItemReminder(
        string $team_id,
        string $project_id,
        string $sprint_id,
        string $item_id,
        string $reminder_id,
    ): array {
        return $this->sprints->deleteItemReminder($team_id, $project_id, $sprint_id, $item_id, $reminder_id);
    }

    #[McpTool(name: 'zoho_get_sprint_timer', description: 'Fetch item timer details for a sprint.')]
    public function getSprintTimer(
        string $team_id,
        string $project_id,
        string $sprint_id,
    ): array {
        return $this->sprints->getSprintTimer($team_id, $project_id, $sprint_id);
    }
}
