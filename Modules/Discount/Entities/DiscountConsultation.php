<?php

namespace Modules\Discount\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DiscountConsultation extends Model
{
    use HasFactory;

    protected $table = 'discount_consultations';

    protected $fillable = [
        'discount_id',
        'consultation_id'
    ];
    
}
