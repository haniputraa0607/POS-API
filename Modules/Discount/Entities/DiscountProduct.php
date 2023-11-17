<?php

namespace Modules\Discount\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DiscountProduct extends Model
{
    use HasFactory;

    protected $table = 'discount_products';

    protected $fillable = [
        'discount_id',
        'product_id'
    ];
    
}
