<?php

namespace Modules\Banner\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Product\Entities\Product;

class Banner extends Model
{
    use HasFactory;

    protected $table = 'banners';
    protected $fillable = ['title', 'product_id'];

    public function product() : BelongsTo {
        return $this->belongsTo(Product::class);
    }

}
