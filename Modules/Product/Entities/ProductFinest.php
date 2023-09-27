<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Product\Entities\Product;

class ProductFinest extends Model
{
    use HasFactory;

    protected $table = 'product_finests';
    protected $fillable = [
        'title',
        'description'
    ];

}
