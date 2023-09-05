<?php

namespace Modules\Customer\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Customer\Entities\TreatmentPatient;
use Modules\Order\Entities\OrderProduct;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TreatmentPatientStep extends Model
{
    use HasFactory;

    protected $table = 'treatment_patient_steps';
    protected $fillable = [
        'treatment_patient_id',
        'step',
        'date',
        'status',
    ];

    public function order_product(): HasOne
    {
        return $this->hasOne(OrderProduct::class);
    }


}
