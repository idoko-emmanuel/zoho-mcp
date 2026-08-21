<?php

namespace App\Services;

use Composer\CaBundle\CaBundle;
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
        return $this->postForm("/team/{$teamId}/projects/", $data);
    }

    public function updateProject(string $teamId, string $projectId, array $data): array
    {
        return $this->postForm("/team/{$teamId}/projects/{$projectId}/", $data);
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
            'type' => '[1,2,3,4]',
        ])->json();
    }

    public function getSprint(string $teamId, string $projectId, string $sprintId): array
    {
        return $this->client()->get("/team/{$teamId}/projects/{$projectId}/sprints/{$sprintId}/", ['action' => 'data'])->json();
    }

    public function createSprint(string $teamId, string $projectId, array $data): array
    {
        return $this->postForm("/team/{$teamId}/projects/{$projectId}/sprints/", $data);
    }

    public function updateSprint(string $teamId, string $projectId, string $sprintId, array $data): array
    {
        return $this->postForm("/team/{$teamId}/projects/{$projectId}/sprints/{$sprintId}/", $data);
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
            'action' => 'sprintitems',
            'subitem' => 'true',
        ])->json();
    }

    public function getItem(string $teamId, string $projectId, string $sprintId, string $itemId): array
    {
        return $this->client()->get("/team/{$teamId}/projects/{$projectId}/sprints/{$sprintId}/item/{$itemId}/", ['action' => 'details'])->json();
    }

    public function createItem(string $teamId, string $projectId, string $sprintId, array $data): array
    {
        return $this->postForm("/team/{$teamId}/projects/{$projectId}/sprints/{$sprintId}/item/", $data);
    }

    public function updateItem(string $teamId, string $projectId, string $sprintId, string $itemId, array $data): array
    {
        return $this->postForm("/team/{$teamId}/projects/{$projectId}/sprints/{$sprintId}/item/{$itemId}/", $data);
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
        return $this->postForm("/team/{$teamId}/projects/{$projectId}/epic/", $data);
    }

    public function updateEpic(string $teamId, string $projectId, string $epicId, array $data): array
    {
        return $this->postForm("/team/{$teamId}/projects/{$projectId}/epic/{$epicId}/", $data);
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
        return $this->postForm("/team/{$teamId}/projects/{$projectId}/sprints/{$sprintId}/item/{$itemId}/notes/", [
            'name' => $content,
        ]);
    }

    public function updateComment(string $teamId, string $projectId, string $sprintId, string $itemId, string $notesId, string $content): array
    {
        return $this->postForm("/team/{$teamId}/projects/{$projectId}/sprints/{$sprintId}/item/{$itemId}/notes/{$notesId}/", [
            'name' => $content,
        ]);
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
    // Project customization (statuses, item types, priorities)
    // ──────────────────────────────────────────────────────────────────────────

    public function listItemStatuses(string $teamId, string $projectId): array
    {
        return $this->client()->get("/team/{$teamId}/projects/{$projectId}/itemstatus/", ['action' => 'data'])->json();
    }

    public function listItemTypes(string $teamId, string $projectId): array
    {
        return $this->client()->get("/team/{$teamId}/projects/{$projectId}/itemtype/", ['action' => 'data'])->json();
    }

    public function listPriorities(string $teamId, string $projectId): array
    {
        return $this->client()->get("/team/{$teamId}/projects/{$projectId}/priority/", ['action' => 'data'])->json();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Subitems
    // ──────────────────────────────────────────────────────────────────────────

    public function createSubitem(string $teamId, string $projectId, string $sprintId, string $itemId, array $data): array
    {
        return $this->postForm("/team/{$teamId}/projects/{$projectId}/sprints/{$sprintId}/item/{$itemId}/subitem/", $data);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Attachments
    // ──────────────────────────────────────────────────────────────────────────

    public function addItemAttachment(string $teamId, string $projectId, string $sprintId, string $itemId, array $data): array
    {
        return $this->postForm("/team/{$teamId}/projects/{$projectId}/sprints/{$sprintId}/item/{$itemId}/attachments/", $data);
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
        // As with followers, Zoho needs an explicit action or it answers
        // 404 "Given URL is wrong".
        return $this->client()->get("/team/{$teamId}/projects/{$projectId}/sprints/{$sprintId}/item/{$itemId}/linkitem/", [
            'action' => 'data',
        ])->json();
    }

    public function linkItems(string $teamId, string $projectId, string $sprintId, string $itemId, array $data): array
    {
        return $this->postForm("/team/{$teamId}/projects/{$projectId}/sprints/{$sprintId}/item/{$itemId}/linkitem/", $data);
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
        return $this->postForm("/team/{$teamId}/projects/{$projectId}/sprints/{$sprintId}/item/{$itemId}/tags/", $data);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Followers
    // ──────────────────────────────────────────────────────────────────────────

    public function getItemFollowers(string $teamId, string $projectId, string $sprintId, string $itemId): array
    {
        // Zoho requires an explicit action here; without it the API answers
        // 404 "Given URL is wrong" rather than a missing-parameter error.
        return $this->client()->get("/team/{$teamId}/projects/{$projectId}/sprints/{$sprintId}/item/{$itemId}/followers/", [
            'action' => 'getfollowers',
        ])->json();
    }

    /**
     * KNOWN BROKEN — the payload contract is unresolved.
     *
     * 'updatefollowers' is the correct action: 'add'/'addfollowers' are
     * rejected with 404 "Given URL is wrong", while this one reaches the
     * handler. The parameter is 'userIds' — omitting it returns 500 "Given
     * userIds are invalid." But supplying it in any position or encoding
     * (form body, query string, JSON, multipart, repeated userIds[], both the
     * Sprints user id and the ZUID) is rejected with 400 "Extra parameter
     * found in URL". Zoho therefore demands a parameter it will not accept,
     * and the endpoint is undocumented publicly.
     *
     * Callers should expect this to throw. Add followers in the Zoho UI until
     * the real contract is known.
     */
    public function updateItemFollowers(string $teamId, string $projectId, string $sprintId, string $itemId, array $data): array
    {
        return $this->postForm(
            "/team/{$teamId}/projects/{$projectId}/sprints/{$sprintId}/item/{$itemId}/followers/?action=updatefollowers",
            $data
        );
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
        return $this->postForm("/team/{$teamId}/projects/{$projectId}/sprints/{$sprintId}/item/{$itemId}/reminder/", $data);
    }

    public function updateItemReminder(string $teamId, string $projectId, string $sprintId, string $itemId, string $reminderId, array $data): array
    {
        return $this->postForm("/team/{$teamId}/projects/{$projectId}/sprints/{$sprintId}/item/{$itemId}/reminder/{$reminderId}/", $data);
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

    /**
     * Zoho rejects this endpoint with a 500 "Parameter missing in Request" unless both
     * `index` and `range` are sent, and its `index` is 1-based (unlike notes/comments).
     */
    public function listTeamMembers(string $teamId, int $index = 1, int $range = 100): array
    {
        return $this->client()->get("/team/{$teamId}/users/", [
            'action' => 'data',
            'index' => $index,
            'range' => $range,
        ])->json();
    }

    public function listProjectMembers(string $teamId, string $projectId, int $index = 1, int $range = 100): array
    {
        return $this->client()->get("/team/{$teamId}/projects/{$projectId}/users/", [
            'action' => 'data',
            'index' => $index,
            'range' => $range,
        ])->json();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Zoho's write endpoints expect application/x-www-form-urlencoded. A JSON body is
     * accepted and then silently ignored: the call returns {"status":"success"} while
     * nothing changes. Every write goes through here so that cannot happen again.
     */
    private function postForm(string $path, array $data): array
    {
        return $this->client()->asForm()->post($path, $data)->json();
    }

    private function client(): PendingRequest
    {
        return Http::withOptions(['verify' => CaBundle::getSystemCaRootBundlePath()])
            ->baseUrl($this->baseUrl)
            ->withToken($this->auth->getValidToken())
            ->acceptJson()
            ->throw();
    }
}
