<?php

namespace Modules\Transaction\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction extends Model
{
    use HasFactory;

    protected $table = 'transactions';
    protected $fillable = [
        'id_outlet',
        'id_customer',
        'id_promo_campaign_code',
        'id_deal_voucher',
        'id_cashier',
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
}
