<?php

namespace Modules\PatientGrievance\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PatientGrievance extends Model
{
    use HasFactory;

    protected $table = 'patient_grievances';
    protected $fillable = [
        'id_transaction_consultation',
        'id_grievance'
    ];
}
