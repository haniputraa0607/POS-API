<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Product\Entities\Product;

class ProductTrending extends Model
{
    use HasFactory;

    protected $table = 'product_trendings';
    protected $fillable = [
        'product_id'
    ];

    public function products()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

}
