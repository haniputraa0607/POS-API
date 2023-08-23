<?php

namespace Modules\Prescription\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SubstanceOutletPrice extends Model
{
    use HasFactory;

    protected $table = 'substance_price_outlets';
    protected $fillable = [
        'substance_id',
        'outlet_id',
        'price',
    ];
}
