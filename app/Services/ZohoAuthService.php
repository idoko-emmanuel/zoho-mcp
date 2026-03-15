<?php

namespace App\Services;

use App\Models\ZohoToken;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ZohoAuthService
{
    private string $accountsUrl;
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;

    public function __construct()
    {
        $this->accountsUrl  = rtrim(config('zoho.accounts_url'), '/');
        $this->clientId     = config('zoho.client_id');
        $this->clientSecret = config('zoho.client_secret');
        $this->redirectUri  = config('zoho.redirect_uri');
    }

    /**
     * Build the Zoho OAuth authorization URL to redirect the user to.
     */
    public function getAuthorizationUrl(): string
    {
        $scopes = implode(',', config('zoho.sprints.scopes'));

        return $this->accountsUrl . '/oauth/v2/auth?' . http_build_query([
            'response_type' => 'code',
            'client_id'     => $this->clientId,
            'scope'         => $scopes,
            'redirect_uri'  => $this->redirectUri,
            'access_type'   => 'offline',
            'prompt'        => 'consent',
        ]);
    }

    /**
     * Exchange the authorization code for access + refresh tokens and persist them.
     */
    public function handleCallback(string $code): ZohoToken
    {
        $response = Http::asForm()->post($this->accountsUrl . '/oauth/v2/token', [
            'grant_type'    => 'authorization_code',
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri'  => $this->redirectUri,
            'code'          => $code,
        ]);

        $this->assertSuccess($response, 'token exchange');

        return $this->persist($response->json());
    }

    /**
     * Return a valid (non-expired) access token, refreshing if necessary.
     */
    public function getValidToken(): string
    {
        $token = ZohoToken::latest()->first();

        if (! $token) {
            throw new RuntimeException(
                'No Zoho token found. Visit ' . url('/zoho/auth') . ' to authorise.'
            );
        }

        if ($token->isExpired()) {
            $token = $this->refresh($token);
        }

        return $token->access_token;
    }

    /**
     * Use the stored refresh token to obtain a new access token.
     */
    private function refresh(ZohoToken $token): ZohoToken
    {
        $response = Http::asForm()->post($this->accountsUrl . '/oauth/v2/token', [
            'grant_type'    => 'refresh_token',
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $token->refresh_token,
        ]);

        $this->assertSuccess($response, 'token refresh');

        $data = $response->json();

        // Zoho does not return a new refresh_token on refresh, keep the old one
        $data['refresh_token'] = $token->refresh_token;

        return $this->persist($data);
    }

    /**
     * Persist token data, truncating old records to keep the table lean.
     */
    private function persist(array $data): ZohoToken
    {
        ZohoToken::truncate();

        return ZohoToken::create([
            'access_token'  => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? null,
            'token_type'    => $data['token_type'] ?? 'Bearer',
            'expires_in'    => $data['expires_in'] ?? 3600,
            'expires_at'    => Carbon::now()->addSeconds(($data['expires_in'] ?? 3600) - 60),
            'api_domain'    => $data['api_domain'] ?? null,
        ]);
    }

    private function assertSuccess(Response $response, string $context): void
    {
        if ($response->failed() || isset($response->json()['error'])) {
            throw new RuntimeException(
                "Zoho {$context} failed: " . ($response->json()['error'] ?? $response->body())
            );
        }
    }
}
