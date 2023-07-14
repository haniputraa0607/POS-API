<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Product\Entities\ProductCategory;
use Modules\Product\Entities\ProductGlobalPrice;
use Modules\Product\Entities\ProductOutletPrice;
use Modules\Product\Entities\ProductOutletStock;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';
    protected $fillable = [
        'product_category_id',
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
        return $query->whereNotNull('product_category_id')->where('type', 'Product');
    }

    public function scopeTreatment(Builder $query): Builder
    {
        return $query->whereNull('product_category_id')->where('type', 'Treatment');
    }

    public function global_price(): HasOne
    {
        return $this->hasOne(ProductGlobalPrice::class);
    }

    public function outlet_price(): HasMany
    {
        return $this->hasMany(ProductOutletPrice::class);
    }

    public function outlet_stock(): HasOne
    {
        return $this->hasOne(ProductOutletStock::class);
    }
}
