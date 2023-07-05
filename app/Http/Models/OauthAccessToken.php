<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OauthAccessToken extends Model
{
    public $incrementing = false;

    protected $casts = [
        'user_id' => 'int',
        'client_id' => 'int',
        'revoked' => 'bool'
    ];

    protected $dates = [
        'expires_at'
    ];

    protected $fillable = [
        'user_id',
        'client_id',
        'name',
        'scopes',
        'revoked',
        'expires_at'
    ];
}
