<?php

namespace Modules\Transaction\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Transaction\Entities\TransactionCash;
use Modules\Order\Entities\Order;
use Modules\Customer\Entities\Customer;

class Transaction extends Model
{
    use HasFactory;

    protected $table = 'transactions';
    protected $fillable = [
        'order_id',
        'outlet_id',
        'customer_id',
        'user_id',
        'transaction_date',
        'completed_at',
        'transaction_receipt_number',
        'transaction_notes',
        'transaction_subtotal',
        'transaction_gross',
        'transaction_discount',
        'transaction_tax',
        'transaction_grandtotal',
        'transaction_payment_type',
        'transaction_payment_status',
        'void_date',
    ];

    public function transaction_cash(): HasOne
    {
        return $this->hasOne(TransactionCash::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }
}
