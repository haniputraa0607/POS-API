<?php

namespace Modules\Order\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Product\Entities\Product;
use Modules\Order\Entities\Order;
use Modules\Customer\Entities\TreatmentPatient;
use Modules\Customer\Entities\TreatmentPatientStep;

class OrderProduct extends Model
{
    use HasFactory;

    protected $table = 'order_products';
    protected $fillable = [
        'order_id',
        'product_id',
        'type',
        'schedule_date',
        'treatment_patient_id',
        'treatment_patient_step_id',
        'qty',
        'order_product_price',
        'order_product_subtotal',
        'order_product_discount',
        'order_product_tax',
        'order_product_grandtotal',
        'queue',
        'queue_code',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    public function treatment_patient(): BelongsTo
    {
        return $this->belongsTo(TreatmentPatient::class, 'treatment_patient_id', 'id');
    }

    public function step(): BelongsTo
    {
        return $this->belongsTo(TreatmentPatientStep::class, 'treatment_patient_step_id', 'id');
    }
}
