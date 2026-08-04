<?php

use App\Mcp\Tools\UserTools;
use App\Services\ZohoAuthService;
use App\Services\ZohoSprintsService;

function teamMembersResponse(): array
{
    return [
        // Zoho calls the account ZUID `iamUserId` here.
        'user_prop' => ['displayName' => 0, 'emailId' => 1, 'isConfirmed' => 2, 'iamUserId' => 3],
        'userJObj' => [
            '166402000000010241' => ['Chinnaya Agara', 'naya@wearecheck.co', true, '868640150'],
            '166402000000027128' => ['Emmanuel Idoko', 'emmanuel@wearecheck.co', true, '870906520'],
        ],
    ];
}

beforeEach(function () {
    $this->auth = Mockery::mock(ZohoAuthService::class);
    $this->sprints = Mockery::mock(ZohoSprintsService::class);
    $this->tools = new UserTools($this->auth, $this->sprints);
});

it('returns the authorising account without a team id', function () {
    $this->auth->shouldReceive('getUserInfo')->andReturn([
        'ZUID' => '870906520',
        'Email' => 'emmanuel@wearecheck.co',
        'Display_Name' => 'Emmanuel Idoko',
    ]);

    expect($this->tools->whoami())->toMatchArray([
        'zuid' => '870906520',
        'email' => 'emmanuel@wearecheck.co',
        'displayName' => 'Emmanuel Idoko',
        'zsUserId' => null,
        'matchedInTeam' => null,
    ]);
});

it('resolves the account to its sprints user id by zuid', function () {
    $this->auth->shouldReceive('getUserInfo')->andReturn([
        'ZUID' => '870906520',
        'Email' => 'emmanuel@wearecheck.co',
    ]);
    $this->sprints->shouldReceive('listTeamMembers')->with('team1')->andReturn(teamMembersResponse());

    $result = $this->tools->whoami('team1');

    expect($result['zsUserId'])->toBe('166402000000027128')
        ->and($result['matchedInTeam'])->toBe('team1');
});

it('falls back to matching on email when the zuid is absent', function () {
    $this->auth->shouldReceive('getUserInfo')->andReturn(['Email' => 'EMMANUEL@wearecheck.co']);
    $this->sprints->shouldReceive('listTeamMembers')->andReturn(teamMembersResponse());

    expect($this->tools->whoami('team1')['zsUserId'])->toBe('166402000000027128');
});

it('reports no match rather than guessing a member', function () {
    $this->auth->shouldReceive('getUserInfo')->andReturn([
        'ZUID' => '999',
        'Email' => 'someone-else@example.com',
    ]);
    $this->sprints->shouldReceive('listTeamMembers')->andReturn(teamMembersResponse());

    $result = $this->tools->whoami('team1');

    expect($result['zsUserId'])->toBeNull()
        ->and($result['matchedInTeam'])->toBeNull();
});

it('surfaces the re-authorisation error when the token lacks the profile scope', function () {
    $this->auth->shouldReceive('getUserInfo')
        ->andThrow(new RuntimeException('This Zoho token was issued without the AaaServer.profile.READ scope.'));

    expect(fn () => $this->tools->whoami())
        ->toThrow(RuntimeException::class, 'AaaServer.profile.READ');
});
