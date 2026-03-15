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

it('posts content when adding a comment', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['comment' => []])]);

    $this->service->addComment('team1', 'proj1', 'sprint1', 'item1', 'Great work!');

    Http::assertSent(fn ($req) =>
        $req->method() === 'POST' &&
        str_contains($req->url(), '/comments/') &&
        $req->data()['content'] === 'Great work!'
    );
});

it('sends a delete request when deleting a comment', function () {
    Http::fake(['sprintsapi.zoho.com/*' => Http::response(['status' => 'success'])]);

    $this->service->deleteComment('team1', 'proj1', 'sprint1', 'item1', 'comment1');

    Http::assertSent(fn ($req) =>
        $req->method() === 'DELETE' &&
        str_contains($req->url(), '/comments/comment1/')
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
