<?php

namespace Modules\Order\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Prescription\Entities\Prescription;

class OrderPrescription extends Model
{
    use HasFactory;

    protected $table = 'order_prescriptions';
    protected $fillable = [
        'order_id',
        'prescription_id',
        'qty',
        'order_prescription_price',
        'order_prescription_subtotal',
        'order_prescription_discount',
        'order_prescription_tax',
        'order_prescription_grandtotal'
    ];

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class, 'prescription_id', 'id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }
}
