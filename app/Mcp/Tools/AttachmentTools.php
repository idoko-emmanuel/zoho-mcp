<?php

namespace App\Mcp\Tools;

use App\Services\ZohoSprintsService;
use PhpMcp\Server\Attributes\McpTool;

class AttachmentTools
{
    public function __construct(private ZohoSprintsService $sprints) {}

    #[McpTool(name: 'zoho_add_item_attachment', description: 'Add an attachment to a Zoho Sprints item. Provide the attachment as a URL via the url parameter.')]
    public function addItemAttachment(
        string $team_id,
        string $project_id,
        string $sprint_id,
        string $item_id,
        string $url,
        ?string $name = null,
    ): array {
        $data = array_filter([
            'url'  => $url,
            'name' => $name,
        ]);

        return $this->sprints->addItemAttachment($team_id, $project_id, $sprint_id, $item_id, $data);
    }

    #[McpTool(name: 'zoho_delete_item_attachment', description: 'Delete an attachment from a Zoho Sprints item.')]
    public function deleteItemAttachment(
        string $team_id,
        string $project_id,
        string $sprint_id,
        string $item_id,
        string $attachment_id,
    ): array {
        return $this->sprints->deleteItemAttachment($team_id, $project_id, $sprint_id, $item_id, $attachment_id);
    }
}
