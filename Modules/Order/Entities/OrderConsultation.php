<?php

namespace Modules\Order\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Order\Entities\Order;
use Modules\User\Entities\User;
use Modules\Doctor\Entities\DoctorShift;

class OrderConsultation extends Model
{
    use HasFactory;

    protected $table = 'order_consultations';
    protected $fillable = [
        'order_id',
        'doctor_id',
        'schedule_date',
        'doctor_shift_id',
        'order_consultation_price',
        'order_consultation_subtotal',
        'order_consultation_discount',
        'order_consultation_tax',
        'order_consultation_grandtotal',
        'status',
        'queue',
        'queue_code',
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id', 'id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(DoctorShift::class, 'doctor_shift_id', 'id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }
}
