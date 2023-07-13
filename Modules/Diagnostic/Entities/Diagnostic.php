<?php

namespace Modules\Diagnostic\Entities;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Diagnostic\Database\factories\DiagnosticFactory;

class Diagnostic extends Model
{
    use HasFactory;

    protected $table = 'diagnostics';
    protected $fillable = [
        'diagnostic_name',
        'description',
        'is_active'
    ];

    public function scopeIsActive(Builder $query) : Builder {
        return $query->where('is_active', 1);
    }

    protected static function newFactory()
    {
        return DiagnosticFactory::new();
    }
}
