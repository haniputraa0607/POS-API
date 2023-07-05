<?php

namespace Modules\Grievance\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Grievance extends Model
{
    use HasFactory;

    protected $table = 'grievances';
    protected $fillable = [
        'grievance_name',
        'description',
        'is_active'
    ];
}
