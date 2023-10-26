<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogActivitiesPosApp extends Model
{
    use HasFactory;

    protected $connection = 'mysql2';

    protected $table = 'log_activities_pos_apps';

    protected $fillable = [
        'module',
        'subject',
        'url',
        'phone',
        'request',
        'response_status',
        'response',
        'ip',
        'useragent',
        'created_at',
        'updated_at'
    ];
}
