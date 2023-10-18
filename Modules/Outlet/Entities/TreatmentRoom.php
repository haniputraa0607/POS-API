<?php

namespace Modules\Outlet\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TreatmentRoom extends Model
{
    use HasFactory;

    protected $table = 'treatment_rooms';
    protected $fillable = [
        'outlet_id',
        'name',
        'is_active',
    ];
}
