<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'zoho.client_id' => 'test-client-id',
        'zoho.client_secret' => 'test-client-secret',
        'zoho.redirect_uri' => 'http://localhost:8000/zoho/callback',
        'zoho.accounts_url' => 'https://accounts.zoho.com',
        'zoho.sprints.scopes' => ['ZohoSprints.teams.READ'],
    ]);
});

it('prints the authorisation url', function () {
    $this->artisan('zoho:auth-url')
        ->expectsOutputToContain('accounts.zoho.com/oauth/v2/auth')
        ->assertSuccessful();
});

it('connects from a pasted callback url', function (string $pasted) {
    Http::fake([
        'accounts.zoho.com/oauth/v2/token' => Http::response([
            'access_token' => 'access-xyz',
            'refresh_token' => 'refresh-xyz',
            'expires_in' => 3600,
        ]),
    ]);

    $this->artisan('zoho:connect', ['callback' => $pasted])
        ->expectsOutputToContain('Zoho authorisation successful')
        ->assertSuccessful();

    Http::assertSent(fn ($request) => $request['code'] === '1000.abc.def');
    $this->assertDatabaseHas('zoho_tokens', ['access_token' => 'access-xyz']);
})->with([
    'full url' => 'http://localhost:8000/zoho/callback?code=1000.abc.def&location=us&accounts-server=https%3A%2F%2Faccounts.zoho.com',
    'quoted url' => "'http://localhost:8000/zoho/callback?code=1000.abc.def'",
    'query string' => 'code=1000.abc.def&location=us',
    'bare code' => '1000.abc.def',
]);

it('fails without calling zoho when the paste has no code', function (string $pasted) {
    Http::fake();

    $this->artisan('zoho:connect', ['callback' => $pasted])
        ->expectsOutputToContain('No authorisation code found')
        ->assertFailed();

    Http::assertNothingSent();
})->with([
    'url without code' => 'http://localhost:8000/zoho/callback?error=access_denied',
    'blank' => '  ',
]);

it('explains how to retry when zoho rejects the code', function () {
    Http::fake([
        'accounts.zoho.com/oauth/v2/token' => Http::response(['error' => 'invalid_code']),
    ]);

    $this->artisan('zoho:connect', ['callback' => '1000.expired'])
        ->expectsOutputToContain('invalid_code')
        ->expectsOutputToContain('zoho:auth-url')
        ->assertFailed();

    $this->assertDatabaseCount('zoho_tokens', 0);
});
