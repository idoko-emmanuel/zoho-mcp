<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\DecodesZohoRecords;
use App\Services\ZohoAuthService;
use App\Services\ZohoSprintsService;
use PhpMcp\Server\Attributes\McpTool;

class UserTools
{
    use DecodesZohoRecords;

    public function __construct(
        private ZohoAuthService $auth,
        private ZohoSprintsService $sprints,
    ) {}

    /**
     * @return array{zuid: string, email: string, displayName: string, zsUserId: string|null, matchedInTeam: string|null}
     */
    #[McpTool(name: 'zoho_whoami', description: 'Identify the Zoho account that authorised this MCP server. Pass team_id to also resolve that account to its Zoho Sprints user id (zsUserId) — the value items carry as ownerId, so you can tell which items are "mine". Requires the AaaServer.profile.READ scope; if the stored token predates it, re-authorise.')]
    public function whoami(?string $team_id = null): array
    {
        $info = $this->auth->getUserInfo();
        $zuid = (string) ($info['ZUID'] ?? $info['zuid'] ?? '');

        $identity = [
            'zuid' => $zuid,
            'email' => (string) ($info['Email'] ?? $info['email'] ?? ''),
            'displayName' => (string) ($info['Display_Name'] ?? $info['displayName'] ?? ''),
            'zsUserId' => null,
            'matchedInTeam' => null,
        ];

        if ($team_id === null) {
            return $identity;
        }

        $members = $this->decodeRecords(
            $this->sprints->listTeamMembers($team_id),
            'userJObj',
            'user_prop',
            // Zoho calls the account-level ZUID `iamUserId` on a Sprints team member.
            ['name' => 'displayName', 'email' => 'emailId', 'zuid' => 'iamUserId'],
        );

        foreach ($members as $member) {
            $matches = ($zuid !== '' && (string) $member['zuid'] === $zuid)
                || ($identity['email'] !== '' && strcasecmp((string) $member['email'], $identity['email']) === 0);

            if ($matches) {
                $identity['zsUserId'] = $member['id'];
                $identity['matchedInTeam'] = $team_id;
                break;
            }
        }

        return $identity;
    }
}
