<?php

namespace App\Http\Controllers;

use App\Services\ZohoAuthService;
use Illuminate\Http\Request;

class ZohoAuthController extends Controller
{
    public function __construct(private ZohoAuthService $auth) {}

    /**
     * Redirect the user to Zoho to authorise the application.
     */
    public function redirect()
    {
        return redirect($this->auth->getAuthorizationUrl());
    }

    /**
     * Handle the OAuth callback from Zoho, exchange code for tokens.
     */
    public function callback(Request $request)
    {
        $request->validate(['code' => 'required|string']);

        $token = $this->auth->handleCallback($request->input('code'));

        return response()->json([
            'message'    => 'Zoho authorisation successful. MCP server is ready.',
            'expires_at' => $token->expires_at->toDateTimeString(),
        ]);
    }
}
