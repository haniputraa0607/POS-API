<?php

namespace Modules\Doctor\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TreatmentRecordType extends Model
{
    use HasFactory;

    protected $table = 'treatment_record_types';

    protected $fillable = [
        'name',
        'description',
        'is_active'
    ];
}
