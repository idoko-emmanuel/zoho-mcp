<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZohoToken extends Model
{
    protected $fillable = [
        'access_token',
        'refresh_token',
        'token_type',
        'expires_in',
        'expires_at',
        'api_domain',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
