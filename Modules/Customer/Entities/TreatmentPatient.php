<?php

namespace Modules\Customer\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\User\Entities\User;
use Modules\Product\Entities\Product;
use Modules\Customer\Entities\Customer;
use Modules\Customer\Entities\TreatmentPatientStep;

class TreatmentPatient extends Model
{
    use HasFactory;

    protected $table = 'treatment_patients';
    protected $fillable = [
        'treatment_id',
        'patient_id',
        'doctor_id',
        'step',
        'progress',
        'status',
        'start_date',
        'timeframe',
        'timeframe_type',
        'expired_date',
        'suggestion',
    ];

    public function treatment(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'treatment_id', 'id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id', 'id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'patient_id', 'id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(TreatmentPatientStep::class);
    }
}
