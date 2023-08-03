<?php

namespace Modules\PatientGrievance\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Grievance\Entities\Grievance;
use Modules\Consultation\Entities\Consultation;

class PatientGrievance extends Model
{
    use HasFactory;

    protected $table = 'patient_grievances';
    protected $fillable = [
        'consultation_id',
        'grievance_id',
        'notes'
    ];

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class, 'consultation_id', 'id');
    }

    public function grievance(): BelongsTo
    {
        return $this->belongsTo(Grievance::class, 'grievance_id', 'id');
    }
}
