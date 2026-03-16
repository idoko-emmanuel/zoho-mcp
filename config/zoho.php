<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Zoho OAuth Credentials
    |--------------------------------------------------------------------------
    | Register your app at https://api-console.zoho.com/
    | Set the redirect URI to: {APP_URL}/zoho/callback
    */

    'client_id'     => env('ZOHO_CLIENT_ID'),
    'client_secret' => env('ZOHO_CLIENT_SECRET'),
    'redirect_uri'  => env('ZOHO_REDIRECT_URI', env('APP_URL') . '/zoho/callback'),

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
        'scopes'   => [
            'ZohoSprints.teams.READ',
            'ZohoSprints.teamusers.READ',
            'ZohoSprints.projects.ALL',
            'ZohoSprints.projectusers.READ',
            'ZohoSprints.sprints.ALL',
            'ZohoSprints.items.ALL',
            'ZohoSprints.epic.ALL',
            'ZohoSprints.comments.ALL',
            'ZohoSprints.settings.READ',
        ],
    ],

];
