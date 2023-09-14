<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Product\Entities\Product;

class ProductPackage extends Model
{
    use HasFactory;

    protected $table = 'product_packages';
    protected $fillable = [
        'package_id',
        'product_id'
    ];


    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

}
