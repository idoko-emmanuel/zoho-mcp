<?php

namespace App\Console\Commands;

use App\Models\ZohoToken;
use Illuminate\Console\Command;

class ZohoRefreshToken extends Command
{
    /**
     * @var string
     */
    protected $signature = 'zoho:refresh-token';

    /**
     * @var string
     */
    protected $description = 'Print the saved Zoho refresh token, to set as ZOHO_REFRESH_TOKEN elsewhere (it signs in as you: keep it private)';

    public function handle(): int
    {
        $refreshToken = ZohoToken::latest()->value('refresh_token');

        if (blank($refreshToken)) {
            $this->error('No refresh token saved. Authorise first (/zoho-mcp:connect).');

            return self::FAILURE;
        }

        $this->line($refreshToken);

        return self::SUCCESS;
    }
}
