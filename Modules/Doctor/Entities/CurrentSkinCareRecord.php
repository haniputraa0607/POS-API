<?php

namespace Modules\Doctor\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CurrentSkinCareRecord extends Model
{
    use HasFactory;

    protected $table = 'current_skin_care_records';

    protected $fillable = [
        "patient_id",
        "order_id",
        "toner",
        "sun_screen",
    ];

    public function current_skin_care_detail_record()
{
    return $this->hasMany(CurrentSkinCareDetailRecord::class, 'current_skin_care_id');
}


}
