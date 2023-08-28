<?php

namespace Modules\Order\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Order\Entities\OrderProduct;
use Modules\Order\Entities\OrderConsultation;
use Modules\Order\Entities\OrderPrescription;
use Modules\Outlet\Entities\Outlet;
use Modules\Customer\Entities\Customer;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';
    protected $fillable = [
        'patient_id',
        'outlet_id',
        'cashier_id',
        'order_date',
        'order_code',
        'notes',
        'order_subtotal',
        'order_gross',
        'order_discount',
        'order_tax',
        'order_grandtotal',
        'send_to_transaction',
        'is_submited',
        'is_submited_doctor',
    ];

    public function order_products(): HasMany
    {
        return $this->hasMany(OrderProduct::class);
    }

    public function order_consultations(): HasMany
    {
        return $this->hasMany(OrderConsultation::class);
    }

    public function order_prescriptions(): HasMany
    {
        return $this->hasMany(OrderPrescription::class);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class, 'outlet_id', 'id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'patient_id', 'id');
    }



}
