<?php

namespace Modules\Doctor\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Doctor\Entities\DoctorSuggestion;
use Modules\Order\Entities\OrderProduct;
use Modules\Product\Entities\Product;
use Modules\User\Entities\User;
use Modules\Doctor\Entities\Nurse;
use Modules\Doctor\Entities\Beautician;

class DoctorSuggestionProduct extends Model
{
    use HasFactory;

    protected $table = 'doctor_suggestion_products';
    protected $fillable = [
        'doctor_suggestion_id',
        'order_product_id',
        'product_id',
        'type',
        'schedule_date',
        'step',
        'total_step',
        'qty',
        'doctor_id',
        'nurse_id',
        'beautician_id',
        'order_product_price',
        'order_product_subtotal',
        'order_product_discount',
        'order_product_tax',
        'order_product_grandtotal',
        'queue_code',
        'not_purchase'
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function doctor_suggestion(): BelongsTo
    {
        return $this->belongsTo(DoctorSuggestion::class, 'doctor_suggestion_id', 'id');
    }

    public function order_product(): BelongsTo
    {
        return $this->belongsTo(OrderProduct::class, 'order_product_id', 'id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id', 'id');
    }

    public function nurse(): BelongsTo
    {
        return $this->belongsTo(Nurse::class, 'nurse_id', 'id');
    }

    public function beautician(): BelongsTo
    {
        return $this->belongsTo(Beautician::class, 'beautician_id', 'id');
    }

}
