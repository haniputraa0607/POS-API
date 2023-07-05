<?php

namespace Modules\Consultation\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Consultation\Database\factories\ConsultationFactory;
use Modules\Customer\Entities\Customer;
use Modules\EmployeeSchedule\Entities\EmployeeSchedule;
use Modules\Queue\Entities\Queue;

class Consultation extends Model
{
    use HasFactory;

    protected $table ="consultations";
    protected $fillable = [
        'customer_id',
        'queue_id',
        'employee_schedule_id',
        'consultation_date',
        'treatment_recomendation',
        'session_end',
    ];

    // public function customer() : BelongsTo {
    //     return $this->belongsTo(Customer::class);
    // }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function queue(): BelongsTo
    {
        return $this->belongsTo(Queue::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(EmployeeSchedule::class);
    }

    protected static function newFactory()
    {
        return ConsultationFactory::new();
    }
}
