<?php

namespace Modules\PatientDiagnostic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PatientDiagnostic extends Model
{
    use HasFactory;

    protected $fillable = [];
    
    protected static function newFactory()
    {
        return \Modules\PatientDiagnostic\Database\factories\PatientDiagnosticFactory::new();
    }
}
