<?php

use App\Http\Controllers\ZohoAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['service' => 'Zoho MCP Server', 'status' => 'running']);
});

// Zoho OAuth flow
Route::prefix('zoho')->group(function () {
    Route::get('/auth', [ZohoAuthController::class, 'redirect'])->name('zoho.auth');
    Route::get('/callback', [ZohoAuthController::class, 'callback'])->name('zoho.callback');
});
