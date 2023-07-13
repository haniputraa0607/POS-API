<?php

namespace Modules\Grievance\Entities;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Grievance\Database\factories\GrievanceFactory;

class Grievance extends Model
{
    use HasFactory;

    protected $table = 'grievances';
    protected $fillable = [
        'grievance_name',
        'description',
        'is_active'
    ];

    public function scopeIsActive(Builder $query) : Builder {
        return $query->where('is_active', 1);
    }

    protected static function newFactory()
    {
        return GrievanceFactory::new();
    }
}
