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
use Modules\Outlet\Entities\Outlet;

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
    ];

    public function order_products(): HasMany
    {
        return $this->hasMany(OrderProduct::class);
    }

    public function order_consultations(): HasMany
    {
        return $this->hasMany(OrderConsultation::class);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class, 'outlet_id', 'id');
    }

}
