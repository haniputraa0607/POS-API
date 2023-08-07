<?php

namespace Modules\Doctor\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Doctor\Database\factories\DoctorScheduleDateFactory;
use Modules\Doctor\Entities\DoctorSchedule;

class DoctorScheduleDate extends Model
{
    use HasFactory;

    protected $table = 'doctor_schedule_dates';
    protected $fillable = [
        'doctor_schedule_id',
        'date'
    ];

    public function doctor_shit(): BelongsTo
    {
        return $this->belongsTo(DoctorShift::class);
    }
    public function doctor_schedule(): BelongsTo
    {
        return $this->belongsTo(DoctorSchedule::class);
    }

    protected static function newFactory()
    {
        return DoctorScheduleDateFactory::new();
    }
}
