<?php

namespace Modules\Transaction\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TransactionConsultation extends Model
{
    use HasFactory;

    protected $table = 'transaction_consultations';
    protected $fillable = [
        'id_transaction',
        'id_doctor',
        'schdule_date',
        'start_time',
        'end_time',
        'treament_recommendations',
        'transaction_consultation_subtotal',
        'transaction_consultation_tax',
        'transaction_consultation_gross',
        'transaction_consultation_discount',
        'transaction_consultation_price',
        'transaction_discount'
    ];
}
