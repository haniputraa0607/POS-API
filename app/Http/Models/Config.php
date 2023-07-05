<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Config extends Model
{
    use HasFactory;

    protected $table = 'configs';

    protected $primaryKey = 'id_config';

    protected $fillable   = [
        'config_name',
        'description',
        'is_active',
        'created_at',
        'updated_at'
    ];
}
