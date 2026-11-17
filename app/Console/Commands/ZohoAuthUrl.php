<?php

namespace App\Console\Commands;

use App\Services\ZohoAuthService;
use Illuminate\Console\Command;

class ZohoAuthUrl extends Command
{
    /**
     * @var string
     */
    protected $signature = 'zoho:auth-url';

    /**
     * @var string
     */
    protected $description = 'Print the Zoho authorisation URL, for sessions where the browser cannot reach localhost';

    public function handle(ZohoAuthService $auth): int
    {
        $this->line($auth->getAuthorizationUrl());

        return self::SUCCESS;
    }
}
