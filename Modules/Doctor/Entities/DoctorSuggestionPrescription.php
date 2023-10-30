<?php

namespace Modules\Doctor\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Doctor\Entities\DoctorSuggestion;
use Modules\Order\Entities\OrderPrescription;
use Modules\Product\Entities\Product;

class DoctorSuggestionPrescription extends Model
{
    use HasFactory;

    protected $table = 'doctor_suggestion_prescriptions';
    protected $fillable = [
        'doctor_suggestion_id',
        'order_prescription_id',
        'prescription_id',
        'qty',
        'order_prescription_price',
        'order_prescription_subtotal',
        'order_prescription_discount',
        'order_prescription_tax',
        'order_prescription_grandtotal',
        'queue_code',
        'not_purchase'
    ];

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class, 'prescription_id', 'id');
    }

    public function doctor_suggestion(): BelongsTo
    {
        return $this->belongsTo(DoctorSuggestion::class, 'doctor_suggestion_id', 'id');
    }

    public function order_prescription(): BelongsTo
    {
        return $this->belongsTo(OrderPrescription::class, 'order_prescription_id', 'id');
    }

}
