<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductOutletPrice extends Model
{
    use HasFactory;

    protected $table = 'product_outlet_prices';
    protected $fillable = [
        'product_id',
        'outlet_id',
        'price',
    ];
}
