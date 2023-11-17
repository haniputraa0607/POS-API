<?php

namespace Modules\Discount\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DiscountOutlet extends Model
{
    use HasFactory;

    protected $table = 'discount_outlets';

    protected $fillable = [
        'discount_id',
        'outlet_id'
    ];
}
