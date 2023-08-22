<?php

namespace Modules\Prescription\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SubstanceStockLog extends Model
{
    use HasFactory;

    protected $table = 'substance_stock_logs';
    protected $fillable = [
        'substance_stock_id',
        'qty',
        'stock_before',
        'stock_after',
        'source',
        'description',
    ];
}
