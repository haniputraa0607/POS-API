<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Product\Entities\ProductCategory;
use Illuminate\Database\Eloquent\Builder;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';
    protected $fillable = [
        'product_categoriy_id',
        'product_code',
        'product_name',
        'type',
        'description',
        'is_active',
        'need_recipe_status',
    ];

    public function product_category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function scopeProduct(Builder $query): Builder
    {
        return $query->whereNotNull('product_categoriy_id')->where('type', 'Product');
    }

    public function scopeTreatment(Builder $query): Builder
    {
        return $query->whereNull('product_categoriy_id')->where('type', 'Treatment');
    }
}
