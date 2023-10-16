<?php

namespace Modules\Outlet\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Outlet\Entities\Outlet;
use Modules\Outlet\Entities\OutletSchedule;
use Modules\EmployeeSchedule\Entities\EmployeeScheduleDate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OutletScheduleShift extends Model
{
    use HasFactory;

    protected $table = 'outlet_schedule_shifts';
    protected $fillable = [
        'outlet_id',
        'outlet_schedule_id',
        'shift',
        'shift_time_start',
        'shift_time_end'
    ];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function outlet_schedule(): BelongsTo
    {
        return $this->belongsTo(OutletSchedule::class);
    }

    public function employee(): HasMany
    {
        return $this->hasMany(EmployeeScheduleDate::class, 'outlet_schedule_shift_id', 'id');
    }

    protected static function newFactory()
    {
        return \Modules\Outlet\Database\factories\OutletScheduleShiftFactory::new();
    }
}
