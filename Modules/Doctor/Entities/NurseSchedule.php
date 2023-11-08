<?php

namespace Modules\Doctor\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Outlet\Entities\Outlet;
use Modules\Doctor\Entities\Nurse;
use Modules\Doctor\Entities\NurseScheduleDate;

class NurseSchedule extends Model
{
    use HasFactory;

    protected $table = 'nurse_schedules';

    protected $fillable = [
        'nurse_id',
        'outlet_id',
        'schedule_month',
        'schedule_year',
    ];

    public function nurse(): BelongsTo
    {
        return $this->belongsTo(Nurse::class);
    }
    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }
    public function schedule_dates(): HasMany
    {
        return $this->hasMany(NurseScheduleDate::class);
    }

}
