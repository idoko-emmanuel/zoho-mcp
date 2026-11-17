<?php

namespace App\Console\Commands;

use App\Services\ZohoAuthService;
use Illuminate\Console\Command;
use Throwable;

class ZohoConnect extends Command
{
    /**
     * @var string
     */
    protected $signature = 'zoho:connect {callback : The URL Zoho redirected the browser to, or just its code}';

    /**
     * @var string
     */
    protected $description = 'Finish Zoho authorisation from a pasted callback URL or code';

    public function handle(ZohoAuthService $auth): int
    {
        $code = $this->extractCode((string) $this->argument('callback'));

        if ($code === null) {
            $this->error('No authorisation code found. Paste the full URL from the browser address bar after approving in Zoho.');

            return self::FAILURE;
        }

        try {
            $token = $auth->handleCallback($code);
        } catch (Throwable $e) {
            $this->error($e->getMessage());
            $this->line('A code works once and expires after about two minutes. Start again with zoho:auth-url.');

            return self::FAILURE;
        }

        $this->info('Zoho authorisation successful. Token expires at '.$token->expires_at->toDateTimeString().'.');

        return self::SUCCESS;
    }

    /**
     * Accept the full callback URL (…/zoho/callback?code=…), its query string, or the bare code.
     */
    private function extractCode(string $callback): ?string
    {
        $callback = trim($callback, " \t\n\r\0\x0B'\"");

        if ($callback === '') {
            return null;
        }

        if (! str_contains($callback, '?') && ! str_contains($callback, '=') && ! str_contains($callback, '://')) {
            return $callback;
        }

        $query = str_contains($callback, '?') ? substr($callback, strpos($callback, '?') + 1) : $callback;
        parse_str(strtok($query, '#') ?: '', $params);

        $code = $params['code'] ?? null;

        return is_string($code) && $code !== '' ? $code : null;
    }
}
