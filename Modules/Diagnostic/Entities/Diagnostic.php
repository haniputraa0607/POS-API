<?php

namespace Modules\Diagnostic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Diagnostic extends Model
{
    use HasFactory;

    protected $fillable = [];
    
    protected static function newFactory()
    {
        return \Modules\Diagnostic\Database\factories\DiagnosticFactory::new();
    }
}
