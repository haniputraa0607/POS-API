<?php

namespace Modules\Doctor\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Doctor\Database\factories\DoctorScheduleFactory;
use Modules\Outlet\Entities\Outlet;
use Modules\Doctor\Entities\DoctorScheduleDate;

class DoctorSchedule extends Model
{
    use HasFactory;

    protected $table = 'doctor_schedules';
    protected $fillable = [
        'user_id',
        'outlet_id',
        'schedule_month',
        'schedule_year',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }
    public function schedule_dates(): HasMany
    {
        return $this->hasMany(DoctorScheduleDate::class);
    }

    protected static function newFactory()
    {
        return DoctorScheduleFactory::new();
    }
}
