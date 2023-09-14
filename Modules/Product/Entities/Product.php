<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Product\Entities\ProductCategory;
use Modules\Product\Entities\ProductGlobalPrice;
use Modules\Product\Entities\ProductOutletPrice;
use Modules\Product\Entities\ProductOutletStock;
use Modules\Product\Entities\TreatmentOutlet;
use Modules\Product\Entities\ProductTrending;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Customer\Entities\TreatmentPatient;
use Modules\Order\Entities\OrderProduct;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';
    protected $fillable = [
        'product_category_id',
        'equal_id',
        'product_code',
        'product_name',
        'equal_name',
        'type',
        'description',
        'image',
        'is_active',
        'need_recipe_status',
    ];

    public function product_category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function product_trending()
    {
        return $this->hasMany(ProductTrending::class, 'product_id');
    }

    public function product_package(){
        return $this->hasMany(ProductPackage::class, 'product_id');
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

    public function outlet_stock(): HasMany
    {
        return $this->hasMany(ProductOutletStock::class);
    }

    public function outlet_treatment(): HasMany
    {
        return $this->hasMany(TreatmentOutlet::class, 'treatment_id', 'id');
    }

    public function treatment_patients(): HasMany
    {
        return $this->hasMany(TreatmentPatient::class, 'treatment_id', 'id');
    }

    public function orders()
    {
        return $this->hasMany(OrderProduct::class);
    }
}
