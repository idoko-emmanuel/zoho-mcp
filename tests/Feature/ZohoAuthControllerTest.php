<?php

use App\Models\ZohoToken;
use Illuminate\Support\Facades\Http;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    config([
        'zoho.client_id'      => 'test-client-id',
        'zoho.client_secret'  => 'test-client-secret',
        'zoho.redirect_uri'   => 'http://localhost/zoho/callback',
        'zoho.accounts_url'   => 'https://accounts.zoho.com',
        'zoho.sprints.scopes' => ['ZohoSprints.teams.READ'],
    ]);
});

// ──────────────────────────────────────────────────────────────────────────────
// GET /zoho/auth
// ──────────────────────────────────────────────────────────────────────────────

it('redirects to zoho authorization url', function () {
    $response = $this->get('/zoho/auth');

    $response->assertRedirectContains('accounts.zoho.com/oauth/v2/auth');
    $response->assertRedirectContains('client_id=test-client-id');
});

// ──────────────────────────────────────────────────────────────────────────────
// GET /zoho/callback
// ──────────────────────────────────────────────────────────────────────────────

it('exchanges code for token and returns success on callback', function () {
    Http::fake([
        'accounts.zoho.com/oauth/v2/token' => Http::response([
            'access_token'  => 'access-xyz',
            'refresh_token' => 'refresh-xyz',
            'token_type'    => 'Bearer',
            'expires_in'    => 3600,
        ]),
    ]);

    $response = $this->get('/zoho/callback?code=auth-code-123');

    $response->assertOk();
    $response->assertJsonFragment(['message' => 'Zoho authorisation successful. MCP server is ready.']);
    $this->assertDatabaseHas('zoho_tokens', ['access_token' => 'access-xyz']);
});

it('returns 422 when code is missing from callback', function () {
    $response = $this->getJson('/zoho/callback');

    $response->assertStatus(422);
});

it('returns json with expires_at on successful callback', function () {
    Http::fake([
        'accounts.zoho.com/oauth/v2/token' => Http::response([
            'access_token'  => 'token-abc',
            'refresh_token' => 'refresh-abc',
            'token_type'    => 'Bearer',
            'expires_in'    => 3600,
        ]),
    ]);

    $response = $this->get('/zoho/callback?code=some-code');

    $response->assertOk();
    $response->assertJsonStructure(['message', 'expires_at']);
});

// ──────────────────────────────────────────────────────────────────────────────
// GET /
// ──────────────────────────────────────────────────────────────────────────────

it('returns server status on the root endpoint', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertJsonFragment(['service' => 'Zoho MCP Server', 'status' => 'running']);
});
