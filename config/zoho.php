<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Zoho OAuth Credentials
    |--------------------------------------------------------------------------
    | Register your app at https://api-console.zoho.com/
    | Set the redirect URI to: {APP_URL}/zoho/callback
    */

    'client_id' => env('ZOHO_CLIENT_ID'),
    'client_secret' => env('ZOHO_CLIENT_SECRET'),
    'redirect_uri' => env('ZOHO_REDIRECT_URI', env('APP_URL').'/zoho/callback'),

    /*
    | Optional. A refresh token from an earlier authorisation. When no login is
    | saved, the server signs in with it instead of asking the user to authorise,
    | so Claude Code cloud sessions (which start empty) work without a login step.
    | It acts as the Zoho user who issued it, so keep it to that user's own
    | environment. Print yours locally with `php artisan zoho:refresh-token`.
    */

    'refresh_token' => env('ZOHO_REFRESH_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Claude Code plugin
    |--------------------------------------------------------------------------
    | Set by the plugin's serve.sh. When present and no token is saved, the
    | server starts the local OAuth server in the background so the link in
    | the "No Zoho token found" error works without any extra steps.
    */

    'plugin_scripts' => env('ZOHO_MCP_PLUGIN_SCRIPTS'),

    /*
    |--------------------------------------------------------------------------
    | Claude Code cloud session
    |--------------------------------------------------------------------------
    | True inside a Claude Code on the web session. The browser cannot reach
    | localhost there, so authorisation goes through /zoho-mcp:connect, where
    | the user pastes the callback URL back in.
    */

    'cloud_session' => env('CLAUDE_CODE_REMOTE') === true || env('CLAUDE_CODE_REMOTE') === 'true',

    /*
    |--------------------------------------------------------------------------
    | Zoho Accounts URL
    |--------------------------------------------------------------------------
    | Use the data centre matching your Zoho account region:
    |   .com  → accounts.zoho.com
    |   .eu   → accounts.zoho.eu
    |   .in   → accounts.zoho.in
    |   .com.au → accounts.zoho.com.au
    */

    'accounts_url' => env('ZOHO_ACCOUNTS_URL', 'https://accounts.zoho.com'),

    /*
    |--------------------------------------------------------------------------
    | Zoho Sprints API
    |--------------------------------------------------------------------------
    */

    'sprints' => [
        'base_url' => env('ZOHO_SPRINTS_URL', 'https://sprintsapi.zoho.com/zsapi'),
        'scopes' => [
            'ZohoSprints.teams.READ',
            'ZohoSprints.teamusers.READ',
            'ZohoSprints.projects.ALL',
            'ZohoSprints.projectusers.READ',
            'ZohoSprints.sprints.ALL',
            'ZohoSprints.items.ALL',
            'ZohoSprints.epic.ALL',
            'ZohoSprints.comments.ALL',
            'ZohoSprints.settings.READ',
            // Identifies who authorised the server (zoho_whoami). Zoho Sprints has no
            // "current user" endpoint of its own, so this comes from Zoho accounts.
            'AaaServer.profile.READ',
        ],
    ],

];
