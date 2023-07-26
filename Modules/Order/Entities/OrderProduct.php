<?php

namespace Modules\Order\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Product\Entities\Product;

class OrderProduct extends Model
{
    use HasFactory;

    protected $table = 'order_products';
    protected $fillable = [
        'order_id',
        'product_id',
        'type',
        'qty',
        'order_product_price',
        'order_product_subtotal',
        'order_product_discount',
        'order_product_tax',
        'order_product_grandtotal',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}
