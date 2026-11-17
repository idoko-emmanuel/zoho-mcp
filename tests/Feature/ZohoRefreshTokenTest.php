<?php

use App\Models\ZohoToken;
use App\Services\ZohoAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'zoho.client_id' => 'test-client-id',
        'zoho.client_secret' => 'test-client-secret',
        'zoho.accounts_url' => 'https://accounts.zoho.com',
        'zoho.refresh_token' => null,
    ]);
});

it('signs in from ZOHO_REFRESH_TOKEN when no login is saved', function () {
    config(['zoho.refresh_token' => 'env-refresh-token']);
    Http::fake([
        'accounts.zoho.com/oauth/v2/token' => Http::response(['access_token' => 'fresh-access', 'expires_in' => 3600]),
    ]);

    expect(app(ZohoAuthService::class)->getValidToken())->toBe('fresh-access');

    Http::assertSent(fn ($request) => $request['grant_type'] === 'refresh_token'
        && $request['refresh_token'] === 'env-refresh-token');
    $this->assertDatabaseHas('zoho_tokens', ['access_token' => 'fresh-access', 'refresh_token' => 'env-refresh-token']);
});

it('reuses the saved login instead of the environment token', function () {
    config(['zoho.refresh_token' => 'env-refresh-token']);
    ZohoToken::create([
        'access_token' => 'saved-access',
        'refresh_token' => 'saved-refresh',
        'expires_in' => 3600,
        'expires_at' => Carbon::now()->addHour(),
    ]);
    Http::fake();

    expect(app(ZohoAuthService::class)->getValidToken())->toBe('saved-access');

    Http::assertNothingSent();
});

it('reports a rejected environment token', function () {
    config(['zoho.refresh_token' => 'revoked-token']);
    Http::fake([
        'accounts.zoho.com/oauth/v2/token' => Http::response(['error' => 'invalid_code']),
    ]);

    expect(fn () => app(ZohoAuthService::class)->getValidToken())
        ->toThrow(RuntimeException::class, 'invalid_code');

    $this->assertDatabaseCount('zoho_tokens', 0);
});

it('asks for authorisation when there is no login and no environment token', function () {
    Http::fake();

    expect(fn () => app(ZohoAuthService::class)->getValidToken())
        ->toThrow(RuntimeException::class, 'No Zoho token found');

    Http::assertNothingSent();
});

it('prints the saved refresh token', function () {
    ZohoToken::create([
        'access_token' => 'a',
        'refresh_token' => 'saved-refresh',
        'expires_in' => 3600,
        'expires_at' => Carbon::now()->addHour(),
    ]);

    $this->artisan('zoho:refresh-token')
        ->expectsOutput('saved-refresh')
        ->assertSuccessful();
});

it('fails when no refresh token is saved', function () {
    $this->artisan('zoho:refresh-token')
        ->expectsOutputToContain('No refresh token saved')
        ->assertFailed();
});
