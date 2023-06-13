<?php

namespace Modules\PatientGrievance\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PatientGrievance extends Model
{
    use HasFactory;

    protected $fillable = [];
    
    protected static function newFactory()
    {
        return \Modules\PatientGrievance\Database\factories\PatientGrievanceFactory::new();
    }
}
