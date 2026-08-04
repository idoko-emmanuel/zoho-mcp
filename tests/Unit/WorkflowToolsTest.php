<?php

use App\Mcp\Tools\WorkflowTools;
use App\Services\ZohoSprintsService;

beforeEach(function () {
    $this->sprints = Mockery::mock(ZohoSprintsService::class);
    $this->tools = new WorkflowTools($this->sprints);
});

it('decodes positional status rows into named fields', function () {
    $this->sprints->shouldReceive('listItemStatuses')->with('team1', 'proj1')->andReturn([
        'workflowName' => 'adidas',
        'status_prop' => [
            'statusName' => 0, 'isDefault' => 1, 'statusDescription' => 2, 'statusPercentage' => 3,
            'statusType' => 4, 'colorCode' => 5, 'isStart' => 6, 'isEnd' => 7,
        ],
        'statusJObj' => [
            '100' => ['To do', true, 'To do', 0, 0, '#fa335c', true, false],
            '200' => ['Done', true, 'Done', 100, 1, '#259e92', false, true],
        ],
    ]);

    $result = $this->tools->listItemStatuses('team1', 'proj1');

    expect($result['workflowName'])->toBe('adidas')
        ->and($result['statuses'])->toHaveCount(2)
        ->and($result['statuses'][0])->toMatchArray([
            'id' => '100', 'name' => 'To do', 'statusType' => 0, 'isStart' => true, 'isEnd' => false,
        ])
        ->and($result['statuses'][1])->toMatchArray([
            'id' => '200', 'name' => 'Done', 'percentage' => 100, 'isEnd' => true,
        ]);
});

it('keys statuses by the id an item carries, not the row contents', function () {
    $this->sprints->shouldReceive('listItemStatuses')->andReturn([
        'status_prop' => ['statusName' => 0],
        'statusJObj' => ['166402000000006557' => ['In progress']],
    ]);

    $result = $this->tools->listItemStatuses('team1', 'proj1');

    expect($result['statuses'][0]['id'])->toBe('166402000000006557');
});

it('decodes item types', function () {
    $this->sprints->shouldReceive('listItemTypes')->andReturn([
        'projItemType_prop' => ['itemTypeName' => 1, 'isDefault' => 2, 'prefix' => 5, 'itemTypeDescription' => 7],
        'projItemTypeJObj' => ['300' => ['ignored', 'Bug', true, '2', 1, 'I', '0', 'Issues.']],
    ]);

    $result = $this->tools->listItemTypes('team1', 'proj1');

    expect($result['itemTypes'][0])->toMatchArray([
        'id' => '300', 'name' => 'Bug', 'prefix' => 'I', 'isDefault' => true, 'description' => 'Issues.',
    ]);
});

it('decodes priorities', function () {
    $this->sprints->shouldReceive('listPriorities')->andReturn([
        'projPriority_prop' => ['priorityName' => 0, 'isDefault' => 1, 'priorityDescription' => 3, 'colorCode' => 4],
        'projPriorityJObj' => ['400' => ['High', true, 'ignored', 'Items prioritized as high.', '#fa335c', '3']],
    ]);

    $result = $this->tools->listPriorities('team1', 'proj1');

    expect($result['priorities'][0])->toMatchArray([
        'id' => '400', 'name' => 'High', 'color' => '#fa335c',
    ]);
});

it('returns an empty list when the workspace has no statuses', function () {
    $this->sprints->shouldReceive('listItemStatuses')->andReturn(['status' => 'success']);

    expect($this->tools->listItemStatuses('team1', 'proj1')['statuses'])->toBe([]);
});
