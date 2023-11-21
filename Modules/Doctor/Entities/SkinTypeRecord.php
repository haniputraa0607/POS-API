<?php

namespace Modules\Doctor\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SkinTypeRecord extends Model
{
    use HasFactory;

    protected $table = "skin_type_records";

    protected $fillable = [
        "patient_id",
        "order_id",
        "skin_type",
        "skin_tone",
        "visible_pores_percentage",
        "visible_pores_description",
        "wrinkles_description",
        "skin_texture"
    ];
    
}
