<?php

namespace Modules\Doctor\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Order\Entities\Order;
use Modules\Customer\Entities\Customer;
use Modules\User\Entities\User;

class DoctorSuggestion extends Model
{
    use HasFactory;

    protected $table = 'doctor_suggestions';
    protected $fillable = [
        'doctor_id',
        'patient_id',
        'order_id',
        'suggestion_date',
        'order_subtotal',
        'order_gross',
        'order_discount',
        'order_tax',
        'order_grandtotal',
        'send_to_transaction',
        'send_to_transaction_date',
        'cancel_date'
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'patient_id', 'id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id', 'id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }
}
