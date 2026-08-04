<?php

use App\Mcp\Tools\ItemTools;
use App\Services\ZohoSprintsService;

/**
 * A trimmed copy of a real /sprintitems/ response: positional rows, a prop map, and the
 * userDisplayName lookup Zoho ships alongside.
 */
function sprintItemsResponse(): array
{
    return [
        'item_prop' => [
            'itemName' => 0, 'description' => 1, 'itemNo' => 2, 'depth' => 3, 'sequence' => 4,
            'statusId' => 5, 'projItemTypeId' => 6, 'projPriorityId' => 7, 'ownerId' => 8,
            'sprintId' => 9, 'parentItem' => 10, 'epicId' => 11, 'points' => 12,
            'startDate' => 13, 'endDate' => 14, 'isNotesAdded' => 15,
        ],
        'itemJObj' => [
            '9001' => [
                'Seeder to delete stale carts',
                '<div>a very long HTML description…</div>',
                '430', '0', '11', 'status-todo', 'type-task', 'prio-high',
                ['user-1'], 'sprint-20', '', '', 0, '-1', '-1', false,
            ],
            '9002' => [
                'Guest checkout fails',
                '<div>another long HTML description…</div>',
                '425', '0', '6', 'status-doing', 'type-bug', 'prio-high',
                ['user-1', 'user-2'], 'sprint-20', '', '', 3, '-1', '-1', true,
            ],
        ],
        'userDisplayName' => ['user-1' => 'Emmanuel Idoko', 'user-2' => 'Chinnaya Agara'],
    ];
}

beforeEach(function () {
    $this->sprints = Mockery::mock(ZohoSprintsService::class);
    $this->tools = new ItemTools($this->sprints);
});

it('returns the raw response by default', function () {
    $raw = sprintItemsResponse();
    $this->sprints->shouldReceive('listItems')->with('t', 'p', 's')->andReturn($raw);

    expect($this->tools->listItems('t', 'p', 's'))->toBe($raw);
});

it('decodes items into named fields when compact', function () {
    $this->sprints->shouldReceive('listItems')->andReturn(sprintItemsResponse());

    $result = $this->tools->listItems('t', 'p', 's', compact: true);

    expect($result['count'])->toBe(2)
        ->and($result['items'][0])->toMatchArray([
            'id' => '9001',
            'itemNo' => '430',
            'name' => 'Seeder to delete stale carts',
            'statusId' => 'status-todo',
            'itemTypeId' => 'type-task',
            'priorityId' => 'prio-high',
            'sprintId' => 'sprint-20',
            'hasComments' => false,
        ]);
});

it('drops the HTML description, which is the bulk of the payload', function () {
    $this->sprints->shouldReceive('listItems')->andReturn(sprintItemsResponse());

    $result = $this->tools->listItems('t', 'p', 's', compact: true);

    expect($result['items'][0])->not->toHaveKey('description')
        ->and(json_encode($result))->not->toContain('long HTML description');
});

it('resolves owner ids to display names', function () {
    $this->sprints->shouldReceive('listItems')->andReturn(sprintItemsResponse());

    $result = $this->tools->listItems('t', 'p', 's', compact: true);

    expect($result['items'][0]['owners'])->toBe(['Emmanuel Idoko'])
        ->and($result['items'][1]['owners'])->toBe(['Emmanuel Idoko', 'Chinnaya Agara'])
        ->and($result['items'][1]['ownerIds'])->toBe(['user-1', 'user-2']);
});

it('keeps an unknown owner id rather than dropping the owner', function () {
    $raw = sprintItemsResponse();
    $raw['userDisplayName'] = [];
    $this->sprints->shouldReceive('listItems')->andReturn($raw);

    $result = $this->tools->listItems('t', 'p', 's', compact: true);

    expect($result['items'][0]['owners'])->toBe(['user-1']);
});

it('compacts an empty sprint without error', function () {
    $this->sprints->shouldReceive('listItems')->andReturn(['status' => 'success']);

    expect($this->tools->listItems('t', 'p', 's', compact: true))->toBe(['items' => [], 'count' => 0]);
});

// ──────────────────────────────────────────────────────────────────────────────
// Field names
//
// Zoho validates parameter names strictly — an unrecognised key comes back as
// `7602 Extra parameter found in URL`, not silently ignored. `status` was one of
// those, which is why moving an item never worked.
// ──────────────────────────────────────────────────────────────────────────────

/** Capture the payload the tool hands to the service. */
function captureItemPayload($sprints, string $method): callable
{
    $captured = new stdClass;
    $captured->data = null;

    $sprints->shouldReceive($method)->andReturnUsing(function (...$args) use ($captured) {
        $captured->data = end($args);

        return ['status' => 'success'];
    });

    return fn () => $captured->data;
}

it('sends status as statusid when moving an item', function () {
    $payload = captureItemPayload($this->sprints, 'updateItem');

    $this->tools->updateItem('t', 'p', 's', 'i', status_id: 'status-code-review');

    expect($payload())->toBe(['statusid' => 'status-code-review']);
});

it('maps every update field to the name Zoho documents', function () {
    $payload = captureItemPayload($this->sprints, 'updateItem');

    $this->tools->updateItem(
        't', 'p', 's', 'i',
        name: 'N', description: 'D', status_id: 'st', priority_id: 'pr',
        item_type_id: 'ty', start_date: '2026-08-01', end_date: '2026-08-09',
        epic_id: 'ep', points: 3,
    );

    expect(array_keys($payload()))->toBe([
        'name', 'description', 'statusid', 'projpriorityid',
        'projitemtypeid', 'startdate', 'enddate', 'epicid', 'point',
    ]);
});

it('omits fields that were not supplied rather than sending empty keys', function () {
    $payload = captureItemPayload($this->sprints, 'updateItem');

    $this->tools->updateItem('t', 'p', 's', 'i', name: 'Renamed');

    expect($payload())->toBe(['name' => 'Renamed']);
});

it('maps create fields to the documented names', function () {
    $payload = captureItemPayload($this->sprints, 'createItem');

    $this->tools->createItem('t', 'p', 's', 'New item', item_type_id: 'ty', priority_id: 'pr');

    expect($payload())->toBe([
        'name' => 'New item',
        'projitemtypeid' => 'ty',
        'projpriorityid' => 'pr',
    ]);
});

it('keeps a zero point value, which a plain array_filter would have dropped', function () {
    $payload = captureItemPayload($this->sprints, 'updateItem');

    $this->tools->updateItem('t', 'p', 's', 'i', points: 0);

    expect($payload())->toBe(['point' => 0]);
});
