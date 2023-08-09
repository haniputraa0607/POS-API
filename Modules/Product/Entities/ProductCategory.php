<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Product\Entities\Product;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductCategory extends Model
{
    use HasFactory;

    protected $table = 'product_categories';
    protected $fillable = [
        'equal_id',
        'equal_name',
        'equal_code',
        'equal_parent_id',
        'product_category_name',
        'description',
        'product_category_photo'
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
