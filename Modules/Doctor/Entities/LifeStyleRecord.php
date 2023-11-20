<?php

namespace Modules\Doctor\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LifeStyleRecord extends Model
{
    use HasFactory;

    protected $table = 'life_style_records';

    protected $fillable = [
        'order_id',
        'value'
    ];
    
}
