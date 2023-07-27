<?php

namespace Modules\Customer\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Customer\Entities\TreatmentPatient;

class TreatmentPatientStep extends Model
{
    use HasFactory;

    protected $table = 'treatment_patient_steps';
    protected $fillable = [
        'treatment_patient_id',
        'step',
        'date'
    ];


}
