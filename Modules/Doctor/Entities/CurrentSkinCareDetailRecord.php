<?php

namespace Modules\Doctor\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CurrentSkinCareDetailRecord extends Model
{
    use HasFactory;

    protected $table = 'current_skin_care_detail_records';

    protected $fillable = [
        "current_skin_care_id",
        "type",
        "product_name",
        "description"
    ];

    public function current_skin_care_record()
    {
        return $this->belongsTo(CurrentSkinCareRecord::class, 'current_skin_care_id');
    }
    
}
