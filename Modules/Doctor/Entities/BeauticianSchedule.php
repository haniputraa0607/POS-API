<?php

namespace Modules\Doctor\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Outlet\Entities\Outlet;
use Modules\Doctor\Entities\Beautician;
use Modules\Doctor\Entities\BeauticianScheduleDate;

class BeauticianSchedule extends Model
{
    use HasFactory;

    protected $table = 'beautician_schedules';

    protected $fillable = [
        'beautician_id',
        'outlet_id',
        'schedule_month',
        'schedule_year',
    ];

    public function beautician(): BelongsTo
    {
        return $this->belongsTo(Beautician::class);
    }
    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }
    public function schedule_dates(): HasMany
    {
        return $this->hasMany(BeauticianScheduleDate::class);
    }
}
