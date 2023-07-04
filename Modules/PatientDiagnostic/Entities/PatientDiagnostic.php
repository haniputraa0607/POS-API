<?php

namespace Modules\PatientDiagnostic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PatientDiagnostic extends Model
{
    use HasFactory;

    protected $table = 'patient_diagnostics';
    protected $fillable = [
        'id_transaction_consultation',
        'id_diagnostic'
    ];
}
