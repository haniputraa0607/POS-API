<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductOutletStockLog extends Model
{
    use HasFactory;

    protected $table = 'product_outlet_stock_logs';
    protected $fillable = [
        'product_outlet_stock_id',
        'qty',
        'stock_before',
        'stock_after',
        'source',
        'description',
    ];
}
