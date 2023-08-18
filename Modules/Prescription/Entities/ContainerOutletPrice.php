<?php

namespace Modules\Prescription\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ContainerOutletPrice extends Model
{
    use HasFactory;

    protected $table = 'container_price_outlets';
    protected $fillable = [
        'container_id',
        'outlet_id',
        'price',
    ];
}
