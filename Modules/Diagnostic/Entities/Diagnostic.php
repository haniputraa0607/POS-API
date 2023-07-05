<?php

namespace Modules\Diagnostic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Diagnostic extends Model
{
    use HasFactory;

    protected $table = 'diagnostics';
    protected $fillable = [
        'diagnostic_name',
        'description',
        'is_active'
    ];
}
