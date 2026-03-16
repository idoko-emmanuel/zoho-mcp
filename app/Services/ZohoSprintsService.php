<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class ZohoSprintsService
{
    private string $baseUrl;

    public function __construct(private ZohoAuthService $auth)
    {
        $this->baseUrl = rtrim(config('zoho.sprints.base_url'), '/');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Teams
    // ──────────────────────────────────────────────────────────────────────────

    public function listTeams(): array
    {
        return $this->client()->get('/teams/')->json();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Projects
    // ──────────────────────────────────────────────────────────────────────────

    public function listProjects(string $teamId): array
    {
        return $this->client()->get("/team/{$teamId}/projects/", ['action' => 'allprojects'])->json();
    }

    public function getProject(string $teamId, string $projectId): array
    {
        return $this->client()->get("/team/{$teamId}/projects/{$projectId}/", ['action' => 'details'])->json();
    }

    public function createProject(string $teamId, array $data): array
    {
        return $this->client()->post("/team/{$teamId}/projects/", $data)->json();
    }

    public function updateProject(string $teamId, string $projectId, array $data): array
    {
        return $this->client()->post("/team/{$teamId}/projects/{$projectId}/", $data)->json();
    }

    public function deleteProject(string $teamId, string $projectId): array
    {
        return $this->client()->delete("/team/{$teamId}/projects/{$projectId}/")->json();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Sprints
    // ──────────────────────────────────────────────────────────────────────────

    public function listSprints(string $teamId, string $projectId): array
    {
        // type=[1,2,3,4] returns all sprint types (active, closed, upcoming, backlog)
        return $this->client()->get("/team/{$teamId}/projects/{$projectId}/sprints/", [
            'action' => 'data',
            'type'   => '[1,2,3,4]',
        ])->json();
    }

    public function getSprint(string $teamId, string $projectId, string $sprintId): array
    {
        return $this->client()->get("/team/{$teamId}/projects/{$projectId}/sprints/{$sprintId}/", ['action' => 'data'])->json();
    }

    public function createSprint(string $teamId, string $projectId, array $data): array
    {
        return $this->client()->post("/team/{$teamId}/projects/{$projectId}/sprints/", $data)->json();
    }

    public function updateSprint(string $teamId, string $projectId, string $sprintId, array $data): array
    {
        return $this->client()->post("/team/{$teamId}/projects/{$projectId}/sprints/{$sprintId}/", $data)->json();
    }

    public function deleteSprint(string $teamId, string $projectId, string $sprintId): array
    {
        return $this->client()->delete("/team/{$teamId}/projects/{$projectId}/sprints/{$sprintId}/")->json();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Items (Tasks)
    // ──────────────────────────────────────────────────────────────────────────

    public function listItems(string $teamId, string $projectId, string $sprintId): array
    {
        return $this->client()->get("/team/{$teamId}/projects/{$projectId}/sprints/{$sprintId}/item/", [
            'action'  => 'sprintitems',
            'subitem' => 'true',
        ])->json();
    }

    public function getItem(string $teamId, string $projectId, string $sprintId, string $itemId): array
    {
        return $this->client()->get("/team/{$teamId}/projects/{$projectId}/sprints/{$sprintId}/item/{$itemId}/", ['action' => 'details'])->json();
    }

    public function createItem(string $teamId, string $projectId, string $sprintId, array $data): array
    {
        return $this->client()->post("/team/{$teamId}/projects/{$projectId}/sprints/{$sprintId}/item/", $data)->json();
    }

    public function updateItem(string $teamId, string $projectId, string $sprintId, string $itemId, array $data): array
    {
        return $this->client()->post("/team/{$teamId}/projects/{$projectId}/sprints/{$sprintId}/item/{$itemId}/", $data)->json();
    }

    public function deleteItem(string $teamId, string $projectId, string $sprintId, string $itemId): array
    {
        return $this->client()->delete("/team/{$teamId}/projects/{$projectId}/sprints/{$sprintId}/item/{$itemId}/")->json();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Epics
    // ──────────────────────────────────────────────────────────────────────────

    public function listEpics(string $teamId, string $projectId): array
    {
        return $this->client()->get("/team/{$teamId}/projects/{$projectId}/epic/", ['action' => 'data'])->json();
    }

    public function getEpic(string $teamId, string $projectId, string $epicId): array
    {
        return $this->client()->get("/team/{$teamId}/projects/{$projectId}/epic/{$epicId}/", ['action' => 'data'])->json();
    }

    public function createEpic(string $teamId, string $projectId, array $data): array
    {
        return $this->client()->post("/team/{$teamId}/projects/{$projectId}/epic/", $data)->json();
    }

    public function updateEpic(string $teamId, string $projectId, string $epicId, array $data): array
    {
        return $this->client()->post("/team/{$teamId}/projects/{$projectId}/epic/{$epicId}/", $data)->json();
    }

    public function deleteEpic(string $teamId, string $projectId, string $epicId): array
    {
        return $this->client()->delete("/team/{$teamId}/projects/{$projectId}/epic/{$epicId}/")->json();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Comments
    // ──────────────────────────────────────────────────────────────────────────

    public function listComments(string $teamId, string $projectId, string $sprintId, string $itemId, int $index = 0, int $range = 20): array
    {
        return $this->client()->get("/team/{$teamId}/projects/{$projectId}/sprints/{$sprintId}/item/{$itemId}/notes/", [
            'index' => $index,
            'range' => $range,
        ])->json();
    }

    public function addComment(string $teamId, string $projectId, string $sprintId, string $itemId, string $content): array
    {
        return $this->client()->asForm()->post("/team/{$teamId}/projects/{$projectId}/sprints/{$sprintId}/item/{$itemId}/notes/", [
            'name' => $content,
        ])->json();
    }

    public function updateComment(string $teamId, string $projectId, string $sprintId, string $itemId, string $notesId, string $content): array
    {
        return $this->client()->asForm()->post("/team/{$teamId}/projects/{$projectId}/sprints/{$sprintId}/item/{$itemId}/notes/{$notesId}/", [
            'name' => $content,
        ])->json();
    }

    public function deleteComment(string $teamId, string $projectId, string $sprintId, string $itemId, string $notesId): array
    {
        return $this->client()->delete("/team/{$teamId}/projects/{$projectId}/sprints/{$sprintId}/item/{$itemId}/notes/{$notesId}/")->json();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Modules
    // ──────────────────────────────────────────────────────────────────────────

    public function listModules(string $teamId): array
    {
        return $this->client()->get("/team/{$teamId}/settings/customization/modules/", ['action' => 'data'])->json();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Subitems
    // ──────────────────────────────────────────────────────────────────────────

    public function createSubitem(string $teamId, string $projectId, string $sprintId, string $itemId, array $data): array
    {
        return $this->client()->post("/team/{$teamId}/projects/{$projectId}/sprints/{$sprintId}/item/{$itemId}/subitem/", $data)->json();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Attachments
    // ──────────────────────────────────────────────────────────────────────────

    public function addItemAttachment(string $teamId, string $projectId, string $sprintId, string $itemId, array $data): array
    {
        return $this->client()->post("/team/{$teamId}/projects/{$projectId}/sprints/{$sprintId}/item/{$itemId}/attachments/", $data)->json();
    }

    public function deleteItemAttachment(string $teamId, string $projectId, string $sprintId, string $itemId, string $attachmentId): array
    {
        return $this->client()->delete("/team/{$teamId}/projects/{$projectId}/sprints/{$sprintId}/item/{$itemId}/attachment/", ['attachmentId' => $attachmentId])->json();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Linked Items
    // ──────────────────────────────────────────────────────────────────────────

    public function getLinkedItems(string $teamId, string $projectId, string $sprintId, string $itemId): array
    {
        return $this->client()->get("/team/{$teamId}/projects/{$projectId}/sprints/{$sprintId}/item/{$itemId}/linkitem/")->json();
    }

    public function linkItems(string $teamId, string $projectId, string $sprintId, string $itemId, array $data): array
    {
        return $this->client()->post("/team/{$teamId}/projects/{$projectId}/sprints/{$sprintId}/item/{$itemId}/linkitem/", $data)->json();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Tags
    // ──────────────────────────────────────────────────────────────────────────

    public function getItemTags(string $teamId, string $projectId, string $sprintId, string $itemId): array
    {
        return $this->client()->get("/team/{$teamId}/projects/{$projectId}/sprints/{$sprintId}/item/{$itemId}/tags/")->json();
    }

    public function updateItemTags(string $teamId, string $projectId, string $sprintId, string $itemId, array $data): array
    {
        return $this->client()->post("/team/{$teamId}/projects/{$projectId}/sprints/{$sprintId}/item/{$itemId}/tags/", $data)->json();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Followers
    // ──────────────────────────────────────────────────────────────────────────

    public function getItemFollowers(string $teamId, string $projectId, string $sprintId, string $itemId): array
    {
        return $this->client()->get("/team/{$teamId}/projects/{$projectId}/sprints/{$sprintId}/item/{$itemId}/followers/")->json();
    }

    public function updateItemFollowers(string $teamId, string $projectId, string $sprintId, string $itemId, array $data): array
    {
        return $this->client()->post("/team/{$teamId}/projects/{$projectId}/sprints/{$sprintId}/item/{$itemId}/followers/", $data)->json();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Reminders
    // ──────────────────────────────────────────────────────────────────────────

    public function getItemReminder(string $teamId, string $projectId, string $sprintId, string $itemId): array
    {
        return $this->client()->get("/team/{$teamId}/projects/{$projectId}/sprints/{$sprintId}/item/{$itemId}/reminder/")->json();
    }

    public function addItemReminder(string $teamId, string $projectId, string $sprintId, string $itemId, array $data): array
    {
        return $this->client()->post("/team/{$teamId}/projects/{$projectId}/sprints/{$sprintId}/item/{$itemId}/reminder/", $data)->json();
    }

    public function updateItemReminder(string $teamId, string $projectId, string $sprintId, string $itemId, string $reminderId, array $data): array
    {
        return $this->client()->post("/team/{$teamId}/projects/{$projectId}/sprints/{$sprintId}/item/{$itemId}/reminder/{$reminderId}/", $data)->json();
    }

    public function deleteItemReminder(string $teamId, string $projectId, string $sprintId, string $itemId, string $reminderId): array
    {
        return $this->client()->delete("/team/{$teamId}/projects/{$projectId}/sprints/{$sprintId}/item/{$itemId}/reminder/{$reminderId}/")->json();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Timer
    // ──────────────────────────────────────────────────────────────────────────

    public function getSprintTimer(string $teamId, string $projectId, string $sprintId): array
    {
        return $this->client()->get("/team/{$teamId}/projects/{$projectId}/sprints/{$sprintId}/timer/")->json();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Users / Members
    // ──────────────────────────────────────────────────────────────────────────

    public function listTeamMembers(string $teamId): array
    {
        return $this->client()->get("/team/{$teamId}/users/", ['action' => 'data'])->json();
    }

    public function listProjectMembers(string $teamId, string $projectId): array
    {
        return $this->client()->get("/team/{$teamId}/projects/{$projectId}/users/", ['action' => 'data'])->json();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────────

    private function client(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withToken($this->auth->getValidToken())
            ->acceptJson()
            ->throw();
    }
}
