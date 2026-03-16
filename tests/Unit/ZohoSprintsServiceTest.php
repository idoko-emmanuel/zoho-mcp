<?php

use App\Services\ZohoAuthService;
use App\Services\ZohoSprintsService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['zoho.sprints.base_url' => 'https://sprintsapi.zoho.com/zsapi']);

    $auth = Mockery::mock(ZohoAuthService::class);
    $auth->shouldReceive('getValidToken')->andReturn('fake-token');

    $this->service = new ZohoSprintsService($auth);
});

// ──────────────────────────────────────────────────────────────────────────────
// Teams
// ──────────────────────────────────────────────────────────────────────────────

it('calls the correct url to list teams', function () {
    Http::fake(['sprintsapi.zoho.com/zsapi/teams/' => Http::response(['teams' => []])]);

    $this->service->listTeams();

    Http::assertSent(fn ($req) => str_contains($req->url(), '/teams/'));
});

// ──────────────────────────────────────────────────────────────────────────────
// Projects
// ──────────────────────────────────────────────────────────────────────────────

it('includes action=allprojects when listing projects', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['projects' => []])]);

    $this->service->listProjects('team1');

    Http::assertSent(fn ($req) =>
        str_contains($req->url(), '/team/team1/projects/') &&
        str_contains($req->url(), 'action=allprojects')
    );
});

it('includes action=details when getting a project', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['project' => []])]);

    $this->service->getProject('team1', 'proj1');

    Http::assertSent(fn ($req) =>
        str_contains($req->url(), '/team/team1/projects/proj1/') &&
        str_contains($req->url(), 'action=details')
    );
});

it('posts to the correct url when creating a project', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['project' => []])]);

    $this->service->createProject('team1', ['name' => 'New Project']);

    Http::assertSent(fn ($req) =>
        $req->method() === 'POST' &&
        str_contains($req->url(), '/team/team1/projects/')
    );
});

// ──────────────────────────────────────────────────────────────────────────────
// Sprints
// ──────────────────────────────────────────────────────────────────────────────

it('includes action=data and all sprint types when listing sprints', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['sprints' => []])]);

    $this->service->listSprints('team1', 'proj1');

    Http::assertSent(fn ($req) =>
        str_contains($req->url(), '/team/team1/projects/proj1/sprints/') &&
        str_contains($req->url(), 'action=data') &&
        str_contains($req->url(), 'type=')
    );
});

it('posts to the correct url when creating a sprint', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['sprint' => []])]);

    $this->service->createSprint('team1', 'proj1', ['name' => 'Sprint 1']);

    Http::assertSent(fn ($req) =>
        $req->method() === 'POST' &&
        str_contains($req->url(), '/team/team1/projects/proj1/sprints/')
    );
});

// ──────────────────────────────────────────────────────────────────────────────
// Items
// ──────────────────────────────────────────────────────────────────────────────

it('includes action=sprintitems and subitem=true when listing items', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['items' => []])]);

    $this->service->listItems('team1', 'proj1', 'sprint1');

    Http::assertSent(fn ($req) =>
        str_contains($req->url(), '/item/') &&
        str_contains($req->url(), 'action=sprintitems') &&
        str_contains($req->url(), 'subitem=true')
    );
});

it('includes action=details when getting an item', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['item' => []])]);

    $this->service->getItem('team1', 'proj1', 'sprint1', 'item1');

    Http::assertSent(fn ($req) =>
        str_contains($req->url(), '/item/item1/') &&
        str_contains($req->url(), 'action=details')
    );
});

it('sends a delete request when deleting an item', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['status' => 'success'])]);

    $this->service->deleteItem('team1', 'proj1', 'sprint1', 'item1');

    Http::assertSent(fn ($req) =>
        $req->method() === 'DELETE' &&
        str_contains($req->url(), '/item/item1/')
    );
});

// ──────────────────────────────────────────────────────────────────────────────
// Epics
// ──────────────────────────────────────────────────────────────────────────────

it('uses singular /epic/ path when listing epics', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['epics' => []])]);

    $this->service->listEpics('team1', 'proj1');

    Http::assertSent(fn ($req) =>
        str_contains($req->url(), '/epic/') &&
        str_contains($req->url(), 'action=data')
    );
});

// ──────────────────────────────────────────────────────────────────────────────
// Comments
// ──────────────────────────────────────────────────────────────────────────────

it('lists comments using the sprints/item/notes url', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['notes' => []])]);

    $this->service->listComments('team1', 'proj1', 'sprint1', 'item1');

    Http::assertSent(fn ($req) =>
        str_contains($req->url(), '/sprints/sprint1/item/item1/notes/')
    );
});

it('posts form-encoded name field when adding a comment', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['note' => []])]);

    $this->service->addComment('team1', 'proj1', 'sprint1', 'item1', 'Great work!');

    Http::assertSent(fn ($req) =>
        $req->method() === 'POST' &&
        str_contains($req->url(), '/sprints/sprint1/item/item1/notes/') &&
        $req->data()['name'] === 'Great work!'
    );
});

it('posts form-encoded name field when updating a comment', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['note' => []])]);

    $this->service->updateComment('team1', 'proj1', 'sprint1', 'item1', 'note1', 'Updated!');

    Http::assertSent(fn ($req) =>
        $req->method() === 'POST' &&
        str_contains($req->url(), '/sprints/sprint1/item/item1/notes/note1/') &&
        $req->data()['name'] === 'Updated!'
    );
});

it('sends a delete request to the correct notes url when deleting a comment', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['status' => 'success'])]);

    $this->service->deleteComment('team1', 'proj1', 'sprint1', 'item1', 'note1');

    Http::assertSent(fn ($req) =>
        $req->method() === 'DELETE' &&
        str_contains($req->url(), '/sprints/sprint1/item/item1/notes/note1/')
    );
});

// ──────────────────────────────────────────────────────────────────────────────
// Modules
// ──────────────────────────────────────────────────────────────────────────────

it('calls the correct url with action=data to list modules', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['modules' => []])]);

    $this->service->listModules('team1');

    Http::assertSent(fn ($req) =>
        str_contains($req->url(), '/team/team1/settings/customization/modules/') &&
        str_contains($req->url(), 'action=data')
    );
});

// ──────────────────────────────────────────────────────────────────────────────
// Subitems
// ──────────────────────────────────────────────────────────────────────────────

it('posts to the subitem url when creating a subitem', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['item' => []])]);

    $this->service->createSubitem('team1', 'proj1', 'sprint1', 'item1', ['name' => 'Sub task']);

    Http::assertSent(fn ($req) =>
        $req->method() === 'POST' &&
        str_contains($req->url(), '/item/item1/subitem/')
    );
});

// ──────────────────────────────────────────────────────────────────────────────
// Attachments
// ──────────────────────────────────────────────────────────────────────────────

it('posts to the attachments url when adding an attachment', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['attachment' => []])]);

    $this->service->addItemAttachment('team1', 'proj1', 'sprint1', 'item1', ['url' => 'https://example.com/file.pdf']);

    Http::assertSent(fn ($req) =>
        $req->method() === 'POST' &&
        str_contains($req->url(), '/item/item1/attachments/')
    );
});

it('sends a delete request when deleting an attachment', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['status' => 'success'])]);

    $this->service->deleteItemAttachment('team1', 'proj1', 'sprint1', 'item1', 'att1');

    Http::assertSent(fn ($req) =>
        $req->method() === 'DELETE' &&
        str_contains($req->url(), '/item/item1/attachment/')
    );
});

// ──────────────────────────────────────────────────────────────────────────────
// Linked Items
// ──────────────────────────────────────────────────────────────────────────────

it('calls the correct url to get linked items', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['linkedItems' => []])]);

    $this->service->getLinkedItems('team1', 'proj1', 'sprint1', 'item1');

    Http::assertSent(fn ($req) =>
        str_contains($req->url(), '/item/item1/linkitem/')
    );
});

it('posts to the linkitem url when linking items', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['status' => 'success'])]);

    $this->service->linkItems('team1', 'proj1', 'sprint1', 'item1', ['linkTypeId' => 'lt1', 'linkedItemId' => 'item2']);

    Http::assertSent(fn ($req) =>
        $req->method() === 'POST' &&
        str_contains($req->url(), '/item/item1/linkitem/')
    );
});

// ──────────────────────────────────────────────────────────────────────────────
// Tags
// ──────────────────────────────────────────────────────────────────────────────

it('calls the correct url to get item tags', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['tags' => []])]);

    $this->service->getItemTags('team1', 'proj1', 'sprint1', 'item1');

    Http::assertSent(fn ($req) =>
        str_contains($req->url(), '/item/item1/tags/')
    );
});

it('posts to the tags url when updating item tags', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['status' => 'success'])]);

    $this->service->updateItemTags('team1', 'proj1', 'sprint1', 'item1', ['tagId' => 'tag1']);

    Http::assertSent(fn ($req) =>
        $req->method() === 'POST' &&
        str_contains($req->url(), '/item/item1/tags/')
    );
});

// ──────────────────────────────────────────────────────────────────────────────
// Followers
// ──────────────────────────────────────────────────────────────────────────────

it('calls the correct url to get item followers', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['followers' => []])]);

    $this->service->getItemFollowers('team1', 'proj1', 'sprint1', 'item1');

    Http::assertSent(fn ($req) =>
        str_contains($req->url(), '/item/item1/followers/')
    );
});

it('posts to the followers url when updating followers', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['status' => 'success'])]);

    $this->service->updateItemFollowers('team1', 'proj1', 'sprint1', 'item1', ['action' => 'add', 'userIds' => 'u1']);

    Http::assertSent(fn ($req) =>
        $req->method() === 'POST' &&
        str_contains($req->url(), '/item/item1/followers/')
    );
});

// ──────────────────────────────────────────────────────────────────────────────
// Reminders
// ──────────────────────────────────────────────────────────────────────────────

it('calls the correct url to get an item reminder', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['reminder' => []])]);

    $this->service->getItemReminder('team1', 'proj1', 'sprint1', 'item1');

    Http::assertSent(fn ($req) =>
        str_contains($req->url(), '/item/item1/reminder/')
    );
});

it('posts to the reminder url when adding a reminder', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['reminder' => []])]);

    $this->service->addItemReminder('team1', 'proj1', 'sprint1', 'item1', ['remindTime' => '1700000000000']);

    Http::assertSent(fn ($req) =>
        $req->method() === 'POST' &&
        str_contains($req->url(), '/item/item1/reminder/')
    );
});

it('posts to the reminder id url when updating a reminder', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['reminder' => []])]);

    $this->service->updateItemReminder('team1', 'proj1', 'sprint1', 'item1', 'rem1', ['remindTime' => '1700000000000']);

    Http::assertSent(fn ($req) =>
        $req->method() === 'POST' &&
        str_contains($req->url(), '/item/item1/reminder/rem1/')
    );
});

it('sends a delete request when deleting a reminder', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['status' => 'success'])]);

    $this->service->deleteItemReminder('team1', 'proj1', 'sprint1', 'item1', 'rem1');

    Http::assertSent(fn ($req) =>
        $req->method() === 'DELETE' &&
        str_contains($req->url(), '/item/item1/reminder/rem1/')
    );
});

// ──────────────────────────────────────────────────────────────────────────────
// Timer
// ──────────────────────────────────────────────────────────────────────────────

it('calls the correct url to get sprint timer', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['timer' => []])]);

    $this->service->getSprintTimer('team1', 'proj1', 'sprint1');

    Http::assertSent(fn ($req) =>
        str_contains($req->url(), '/sprints/sprint1/timer/')
    );
});

// ──────────────────────────────────────────────────────────────────────────────
// Auth header
// ──────────────────────────────────────────────────────────────────────────────

it('sends the bearer token on every request', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['teams' => []])]);

    $this->service->listTeams();

    Http::assertSent(fn ($req) =>
        $req->hasHeader('Authorization', 'Bearer fake-token')
    );
});
