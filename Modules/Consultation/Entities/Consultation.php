<?php

namespace Modules\Consultation\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Consultation\Database\factories\ConsultationFactory;
use Modules\Customer\Entities\Customer;
use Modules\EmployeeSchedule\Entities\EmployeeSchedule;
use Modules\Queue\Entities\Queue;
use Modules\Order\Entities\OrderConsultation;
use Modules\PatientGrievance\Entities\PatientGrievance;
use Modules\PatientDiagnostic\Entities\PatientDiagnostic;

class Consultation extends Model
{
    use HasFactory;

    protected $table ="consultations";
    protected $fillable = [
        'order_consultation_id',
        'treatment_recomendation',
        'session_end',
        'is_edit'
    ];

    public function order_consultation() : BelongsTo {
        return $this->belongsTo(OrderConsultation::class);
    }

    public function patient_diagnostic(): HasMany
    {
        return $this->hasMany(PatientDiagnostic::class, 'consultation_id');
    }

    public function patient_grievance(): HasMany
    {
        return $this->hasMany(PatientGrievance::class, 'consultation_id');
    }

    protected static function newFactory()
    {
        return ConsultationFactory::new();
    }
}
