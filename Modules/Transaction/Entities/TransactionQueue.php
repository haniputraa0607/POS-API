<?php

namespace Modules\Transaction\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TransactionQueue extends Model
{
    use HasFactory;

    protected $table = 'transaction_queues';
    protected $fillable = [
        'id_transaction',
        'consultation_queue',
        'product_queue',
        'treatment_queue'
    ];
}
