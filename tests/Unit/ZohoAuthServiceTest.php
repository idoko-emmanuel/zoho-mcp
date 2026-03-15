<?php

use App\Models\ZohoToken;
use App\Services\ZohoAuthService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    config([
        'zoho.client_id'      => 'test-client-id',
        'zoho.client_secret'  => 'test-client-secret',
        'zoho.redirect_uri'   => 'http://localhost/zoho/callback',
        'zoho.accounts_url'   => 'https://accounts.zoho.com',
        'zoho.sprints.scopes' => ['ZohoSprints.teams.READ', 'ZohoSprints.projects.ALL'],
    ]);
    $this->service = new ZohoAuthService();
});

// ──────────────────────────────────────────────────────────────────────────────
// Authorization URL
// ──────────────────────────────────────────────────────────────────────────────

it('builds a valid authorization url', function () {
    $url = $this->service->getAuthorizationUrl();

    expect($url)
        ->toContain('accounts.zoho.com/oauth/v2/auth')
        ->toContain('response_type=code')
        ->toContain('client_id=test-client-id')
        ->toContain('access_type=offline')
        ->toContain('prompt=consent')
        ->toContain('ZohoSprints.teams.READ')
        ->toContain('ZohoSprints.projects.ALL');
});

// ──────────────────────────────────────────────────────────────────────────────
// Callback / token exchange
// ──────────────────────────────────────────────────────────────────────────────

it('persists tokens after a successful callback', function () {
    Http::fake([
        'accounts.zoho.com/oauth/v2/token' => Http::response([
            'access_token'  => 'access-abc',
            'refresh_token' => 'refresh-abc',
            'token_type'    => 'Bearer',
            'expires_in'    => 3600,
            'api_domain'    => 'https://www.zohoapis.com',
        ]),
    ]);

    $token = $this->service->handleCallback('auth-code-123');

    expect($token->access_token)->toBe('access-abc');
    $this->assertDatabaseHas('zoho_tokens', [
        'access_token'  => 'access-abc',
        'refresh_token' => 'refresh-abc',
    ]);
});

it('throws when zoho returns an error on callback', function () {
    Http::fake([
        'accounts.zoho.com/oauth/v2/token' => Http::response(['error' => 'invalid_code']),
    ]);

    expect(fn () => $this->service->handleCallback('bad-code'))
        ->toThrow(RuntimeException::class);
});

it('replaces old tokens on new callback', function () {
    ZohoToken::create([
        'access_token'  => 'old-token',
        'refresh_token' => 'old-refresh',
        'token_type'    => 'Bearer',
        'expires_in'    => 3600,
        'expires_at'    => Carbon::now()->addHour(),
    ]);

    Http::fake([
        'accounts.zoho.com/oauth/v2/token' => Http::response([
            'access_token'  => 'new-token',
            'refresh_token' => 'new-refresh',
            'token_type'    => 'Bearer',
            'expires_in'    => 3600,
        ]),
    ]);

    $this->service->handleCallback('new-auth-code');

    expect(ZohoToken::count())->toBe(1);
    $this->assertDatabaseHas('zoho_tokens', ['access_token' => 'new-token']);
    $this->assertDatabaseMissing('zoho_tokens', ['access_token' => 'old-token']);
});

// ──────────────────────────────────────────────────────────────────────────────
// getValidToken
// ──────────────────────────────────────────────────────────────────────────────

it('returns a valid access token when not expired', function () {
    ZohoToken::create([
        'access_token'  => 'still-valid',
        'refresh_token' => 'refresh-xyz',
        'token_type'    => 'Bearer',
        'expires_in'    => 3600,
        'expires_at'    => Carbon::now()->addHour(),
    ]);

    expect($this->service->getValidToken())->toBe('still-valid');
});

it('silently refreshes an expired token', function () {
    ZohoToken::create([
        'access_token'  => 'expired-token',
        'refresh_token' => 'my-refresh',
        'token_type'    => 'Bearer',
        'expires_in'    => 3600,
        'expires_at'    => Carbon::now()->subMinute(),
    ]);

    Http::fake([
        'accounts.zoho.com/oauth/v2/token' => Http::response([
            'access_token' => 'refreshed-token',
            'token_type'   => 'Bearer',
            'expires_in'   => 3600,
        ]),
    ]);

    expect($this->service->getValidToken())->toBe('refreshed-token');
    $this->assertDatabaseHas('zoho_tokens', ['access_token' => 'refreshed-token']);
});

it('preserves the refresh token after a silent refresh', function () {
    ZohoToken::create([
        'access_token'  => 'expired-token',
        'refresh_token' => 'keep-me',
        'token_type'    => 'Bearer',
        'expires_in'    => 3600,
        'expires_at'    => Carbon::now()->subMinute(),
    ]);

    Http::fake([
        'accounts.zoho.com/oauth/v2/token' => Http::response([
            'access_token' => 'refreshed-token',
            'token_type'   => 'Bearer',
            'expires_in'   => 3600,
            // Zoho does not return a new refresh_token on refresh
        ]),
    ]);

    $this->service->getValidToken();

    $this->assertDatabaseHas('zoho_tokens', [
        'access_token'  => 'refreshed-token',
        'refresh_token' => 'keep-me',
    ]);
});

it('throws when no token has been stored yet', function () {
    expect(fn () => $this->service->getValidToken())
        ->toThrow(RuntimeException::class, 'No Zoho token found');
});
