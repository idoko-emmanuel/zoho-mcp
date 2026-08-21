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

    Http::assertSent(fn ($req) => str_contains($req->url(), '/team/team1/projects/') &&
        str_contains($req->url(), 'action=allprojects')
    );
});

it('includes action=details when getting a project', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['project' => []])]);

    $this->service->getProject('team1', 'proj1');

    Http::assertSent(fn ($req) => str_contains($req->url(), '/team/team1/projects/proj1/') &&
        str_contains($req->url(), 'action=details')
    );
});

it('posts to the correct url when creating a project', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['project' => []])]);

    $this->service->createProject('team1', ['name' => 'New Project']);

    Http::assertSent(fn ($req) => $req->method() === 'POST' &&
        str_contains($req->url(), '/team/team1/projects/')
    );
});

// ──────────────────────────────────────────────────────────────────────────────
// Sprints
// ──────────────────────────────────────────────────────────────────────────────

it('includes action=data and all sprint types when listing sprints', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['sprints' => []])]);

    $this->service->listSprints('team1', 'proj1');

    Http::assertSent(fn ($req) => str_contains($req->url(), '/team/team1/projects/proj1/sprints/') &&
        str_contains($req->url(), 'action=data') &&
        str_contains($req->url(), 'type=')
    );
});

it('posts to the correct url when creating a sprint', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['sprint' => []])]);

    $this->service->createSprint('team1', 'proj1', ['name' => 'Sprint 1']);

    Http::assertSent(fn ($req) => $req->method() === 'POST' &&
        str_contains($req->url(), '/team/team1/projects/proj1/sprints/')
    );
});

// ──────────────────────────────────────────────────────────────────────────────
// Items
// ──────────────────────────────────────────────────────────────────────────────

it('includes action=sprintitems and subitem=true when listing items', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['items' => []])]);

    $this->service->listItems('team1', 'proj1', 'sprint1');

    Http::assertSent(fn ($req) => str_contains($req->url(), '/item/') &&
        str_contains($req->url(), 'action=sprintitems') &&
        str_contains($req->url(), 'subitem=true')
    );
});

it('includes action=details when getting an item', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['item' => []])]);

    $this->service->getItem('team1', 'proj1', 'sprint1', 'item1');

    Http::assertSent(fn ($req) => str_contains($req->url(), '/item/item1/') &&
        str_contains($req->url(), 'action=details')
    );
});

it('sends a delete request when deleting an item', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['status' => 'success'])]);

    $this->service->deleteItem('team1', 'proj1', 'sprint1', 'item1');

    Http::assertSent(fn ($req) => $req->method() === 'DELETE' &&
        str_contains($req->url(), '/item/item1/')
    );
});

// ──────────────────────────────────────────────────────────────────────────────
// Epics
// ──────────────────────────────────────────────────────────────────────────────

it('uses singular /epic/ path when listing epics', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['epics' => []])]);

    $this->service->listEpics('team1', 'proj1');

    Http::assertSent(fn ($req) => str_contains($req->url(), '/epic/') &&
        str_contains($req->url(), 'action=data')
    );
});

// ──────────────────────────────────────────────────────────────────────────────
// Comments
// ──────────────────────────────────────────────────────────────────────────────

it('lists comments using the sprints/item/notes url with index and range', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['notes' => []])]);

    $this->service->listComments('team1', 'proj1', 'sprint1', 'item1');

    Http::assertSent(fn ($req) => str_contains($req->url(), '/sprints/sprint1/item/item1/notes/') &&
        str_contains($req->url(), 'index=0') &&
        str_contains($req->url(), 'range=20')
    );
});

it('posts form-encoded name field when adding a comment', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['note' => []])]);

    $this->service->addComment('team1', 'proj1', 'sprint1', 'item1', 'Great work!');

    Http::assertSent(fn ($req) => $req->method() === 'POST' &&
        str_contains($req->url(), '/sprints/sprint1/item/item1/notes/') &&
        $req->data()['name'] === 'Great work!'
    );
});

it('posts form-encoded name field when updating a comment', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['note' => []])]);

    $this->service->updateComment('team1', 'proj1', 'sprint1', 'item1', 'note1', 'Updated!');

    Http::assertSent(fn ($req) => $req->method() === 'POST' &&
        str_contains($req->url(), '/sprints/sprint1/item/item1/notes/note1/') &&
        $req->data()['name'] === 'Updated!'
    );
});

it('sends a delete request to the correct notes url when deleting a comment', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['status' => 'success'])]);

    $this->service->deleteComment('team1', 'proj1', 'sprint1', 'item1', 'note1');

    Http::assertSent(fn ($req) => $req->method() === 'DELETE' &&
        str_contains($req->url(), '/sprints/sprint1/item/item1/notes/note1/')
    );
});

// ──────────────────────────────────────────────────────────────────────────────
// Modules
// ──────────────────────────────────────────────────────────────────────────────

it('calls the correct url with action=data to list modules', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['modules' => []])]);

    $this->service->listModules('team1');

    Http::assertSent(fn ($req) => str_contains($req->url(), '/team/team1/settings/customization/modules/') &&
        str_contains($req->url(), 'action=data')
    );
});

// ──────────────────────────────────────────────────────────────────────────────
// Subitems
// ──────────────────────────────────────────────────────────────────────────────

it('posts to the subitem url when creating a subitem', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['item' => []])]);

    $this->service->createSubitem('team1', 'proj1', 'sprint1', 'item1', ['name' => 'Sub task']);

    Http::assertSent(fn ($req) => $req->method() === 'POST' &&
        str_contains($req->url(), '/item/item1/subitem/')
    );
});

// ──────────────────────────────────────────────────────────────────────────────
// Attachments
// ──────────────────────────────────────────────────────────────────────────────

it('posts to the attachments url when adding an attachment', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['attachment' => []])]);

    $this->service->addItemAttachment('team1', 'proj1', 'sprint1', 'item1', ['url' => 'https://example.com/file.pdf']);

    Http::assertSent(fn ($req) => $req->method() === 'POST' &&
        str_contains($req->url(), '/item/item1/attachments/')
    );
});

it('sends a delete request when deleting an attachment', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['status' => 'success'])]);

    $this->service->deleteItemAttachment('team1', 'proj1', 'sprint1', 'item1', 'att1');

    Http::assertSent(fn ($req) => $req->method() === 'DELETE' &&
        str_contains($req->url(), '/item/item1/attachment/')
    );
});

// ──────────────────────────────────────────────────────────────────────────────
// Linked Items
// ──────────────────────────────────────────────────────────────────────────────

it('calls the correct url to get linked items', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['linkedItems' => []])]);

    $this->service->getLinkedItems('team1', 'proj1', 'sprint1', 'item1');

    Http::assertSent(fn ($req) => str_contains($req->url(), '/item/item1/linkitem/')
        && str_contains($req->url(), 'action=data')
    );
});

it('posts to the linkitem url when linking items', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['status' => 'success'])]);

    $this->service->linkItems('team1', 'proj1', 'sprint1', 'item1', ['linkTypeId' => 'lt1', 'linkedItemId' => 'item2']);

    Http::assertSent(fn ($req) => $req->method() === 'POST' &&
        str_contains($req->url(), '/item/item1/linkitem/')
    );
});

// ──────────────────────────────────────────────────────────────────────────────
// Tags
// ──────────────────────────────────────────────────────────────────────────────

it('calls the correct url to get item tags', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['tags' => []])]);

    $this->service->getItemTags('team1', 'proj1', 'sprint1', 'item1');

    Http::assertSent(fn ($req) => str_contains($req->url(), '/item/item1/tags/')
    );
});

it('posts to the tags url when updating item tags', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['status' => 'success'])]);

    $this->service->updateItemTags('team1', 'proj1', 'sprint1', 'item1', ['tagId' => 'tag1']);

    Http::assertSent(fn ($req) => $req->method() === 'POST' &&
        str_contains($req->url(), '/item/item1/tags/')
    );
});

// ──────────────────────────────────────────────────────────────────────────────
// Followers
// ──────────────────────────────────────────────────────────────────────────────

it('calls the correct url to get item followers', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['followers' => []])]);

    $this->service->getItemFollowers('team1', 'proj1', 'sprint1', 'item1');

    // The action is mandatory — without it Zoho answers 404 "Given URL is
    // wrong", which is why asserting on the path alone missed the bug.
    Http::assertSent(fn ($req) => str_contains($req->url(), '/item/item1/followers/')
        && str_contains($req->url(), 'action=getfollowers')
    );
});

it('posts to the followers url when updating followers', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['status' => 'success'])]);

    $this->service->updateItemFollowers('team1', 'proj1', 'sprint1', 'item1', ['userIds' => 'u1']);

    Http::assertSent(fn ($req) => $req->method() === 'POST' &&
        str_contains($req->url(), '/item/item1/followers/')
        && str_contains($req->url(), 'action=updatefollowers')
    );
});

// ──────────────────────────────────────────────────────────────────────────────
// Reminders
// ──────────────────────────────────────────────────────────────────────────────

it('calls the correct url to get an item reminder', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['reminder' => []])]);

    $this->service->getItemReminder('team1', 'proj1', 'sprint1', 'item1');

    Http::assertSent(fn ($req) => str_contains($req->url(), '/item/item1/reminder/')
    );
});

it('posts to the reminder url when adding a reminder', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['reminder' => []])]);

    $this->service->addItemReminder('team1', 'proj1', 'sprint1', 'item1', ['remindTime' => '1700000000000']);

    Http::assertSent(fn ($req) => $req->method() === 'POST' &&
        str_contains($req->url(), '/item/item1/reminder/')
    );
});

it('posts to the reminder id url when updating a reminder', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['reminder' => []])]);

    $this->service->updateItemReminder('team1', 'proj1', 'sprint1', 'item1', 'rem1', ['remindTime' => '1700000000000']);

    Http::assertSent(fn ($req) => $req->method() === 'POST' &&
        str_contains($req->url(), '/item/item1/reminder/rem1/')
    );
});

it('sends a delete request when deleting a reminder', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['status' => 'success'])]);

    $this->service->deleteItemReminder('team1', 'proj1', 'sprint1', 'item1', 'rem1');

    Http::assertSent(fn ($req) => $req->method() === 'DELETE' &&
        str_contains($req->url(), '/item/item1/reminder/rem1/')
    );
});

// ──────────────────────────────────────────────────────────────────────────────
// Timer
// ──────────────────────────────────────────────────────────────────────────────

it('calls the correct url to get sprint timer', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['timer' => []])]);

    $this->service->getSprintTimer('team1', 'proj1', 'sprint1');

    Http::assertSent(fn ($req) => str_contains($req->url(), '/sprints/sprint1/timer/')
    );
});

// ──────────────────────────────────────────────────────────────────────────────
// Members — Zoho 500s on these endpoints unless index+range are sent
// ──────────────────────────────────────────────────────────────────────────────

it('sends a 1-based index and range when listing team members', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['userJObj' => []])]);

    $this->service->listTeamMembers('team1');

    Http::assertSent(fn ($req) => str_contains($req->url(), '/team/team1/users/') &&
        str_contains($req->url(), 'action=data') &&
        str_contains($req->url(), 'index=1') &&
        str_contains($req->url(), 'range=100')
    );
});

it('sends index and range when listing project members', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['userJObj' => []])]);

    $this->service->listProjectMembers('team1', 'proj1', 3, 25);

    Http::assertSent(fn ($req) => str_contains($req->url(), '/team/team1/projects/proj1/users/') &&
        str_contains($req->url(), 'index=3') &&
        str_contains($req->url(), 'range=25')
    );
});

// ──────────────────────────────────────────────────────────────────────────────
// Project customization
// ──────────────────────────────────────────────────────────────────────────────

it('includes action=data when listing item statuses', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['statusJObj' => []])]);

    $this->service->listItemStatuses('team1', 'proj1');

    Http::assertSent(fn ($req) => str_contains($req->url(), '/team/team1/projects/proj1/itemstatus/') &&
        str_contains($req->url(), 'action=data')
    );
});

it('includes action=data when listing item types', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['projItemTypeJObj' => []])]);

    $this->service->listItemTypes('team1', 'proj1');

    Http::assertSent(fn ($req) => str_contains($req->url(), '/team/team1/projects/proj1/itemtype/') &&
        str_contains($req->url(), 'action=data')
    );
});

it('includes action=data when listing priorities', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['projPriorityJObj' => []])]);

    $this->service->listPriorities('team1', 'proj1');

    Http::assertSent(fn ($req) => str_contains($req->url(), '/team/team1/projects/proj1/priority/') &&
        str_contains($req->url(), 'action=data')
    );
});

// ──────────────────────────────────────────────────────────────────────────────
// Auth header
// ──────────────────────────────────────────────────────────────────────────────

it('sends the bearer token on every request', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['teams' => []])]);

    $this->service->listTeams();

    Http::assertSent(fn ($req) => $req->hasHeader('Authorization', 'Bearer fake-token')
    );
});

// ──────────────────────────────────────────────────────────────────────────────
// Write encoding
//
// Zoho accepts a JSON body on its write endpoints and silently ignores it — the
// response is {"status":"success"} and nothing changes. Asserting only the URL,
// as the tests above do, cannot tell the two apart. These assert the encoding.
// ──────────────────────────────────────────────────────────────────────────────

$writes = [
    'updateItem' => fn ($s) => $s->updateItem('team1', 'proj1', 'sprint1', 'item1', ['status' => 'st1']),
    'createItem' => fn ($s) => $s->createItem('team1', 'proj1', 'sprint1', ['name' => 'New']),
    'createSubitem' => fn ($s) => $s->createSubitem('team1', 'proj1', 'sprint1', 'item1', ['name' => 'Sub']),
    'createProject' => fn ($s) => $s->createProject('team1', ['name' => 'P']),
    'updateProject' => fn ($s) => $s->updateProject('team1', 'proj1', ['name' => 'P']),
    'createSprint' => fn ($s) => $s->createSprint('team1', 'proj1', ['name' => 'S']),
    'updateSprint' => fn ($s) => $s->updateSprint('team1', 'proj1', 'sprint1', ['name' => 'S']),
    'createEpic' => fn ($s) => $s->createEpic('team1', 'proj1', ['name' => 'E']),
    'updateEpic' => fn ($s) => $s->updateEpic('team1', 'proj1', 'epic1', ['name' => 'E']),
    'addComment' => fn ($s) => $s->addComment('team1', 'proj1', 'sprint1', 'item1', 'hi'),
    'updateComment' => fn ($s) => $s->updateComment('team1', 'proj1', 'sprint1', 'item1', 'note1', 'hi'),
    'addItemAttachment' => fn ($s) => $s->addItemAttachment('team1', 'proj1', 'sprint1', 'item1', ['url' => 'https://e.com/a.png']),
    'linkItems' => fn ($s) => $s->linkItems('team1', 'proj1', 'sprint1', 'item1', ['linkItemId' => 'i2']),
    'updateItemTags' => fn ($s) => $s->updateItemTags('team1', 'proj1', 'sprint1', 'item1', ['tagId' => 't1']),
    'updateItemFollowers' => fn ($s) => $s->updateItemFollowers('team1', 'proj1', 'sprint1', 'item1', ['userIds' => 'u1']),
    'addItemReminder' => fn ($s) => $s->addItemReminder('team1', 'proj1', 'sprint1', 'item1', ['remindAt' => 'x']),
    'updateItemReminder' => fn ($s) => $s->updateItemReminder('team1', 'proj1', 'sprint1', 'item1', 'r1', ['remindAt' => 'x']),
];

foreach ($writes as $name => $call) {
    it("form-encodes the body of {$name}", function () use ($call) {
        Http::fake(['sprintsapi.zoho.com/*' => Http::response(['status' => 'success'])]);

        $call($this->service);

        Http::assertSent(fn ($req) => $req->method() === 'POST' &&
            $req->hasHeader('Content-Type', 'application/x-www-form-urlencoded')
        );
    });
}

it('sends the status field as form data when moving an item', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['status' => 'success'])]);

    $this->service->updateItem('team1', 'proj1', 'sprint1', 'item1', ['status' => 'status-code-review']);

    Http::assertSent(fn ($req) => $req->data()['status'] === 'status-code-review' &&
        $req->body() === 'status=status-code-review'
    );
});
