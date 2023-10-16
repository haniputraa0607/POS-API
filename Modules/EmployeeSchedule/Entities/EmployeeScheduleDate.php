<?php

namespace Modules\EmployeeSchedule\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\EmployeeSchedule\Entities\EmployeeSchedule;
use Modules\Cashier\Entities\EmployeeAttendance;
use Modules\Outlet\Entities\OutletScheduleShift;

class EmployeeScheduleDate extends Model
{
    use HasFactory;

    protected $table = 'employee_schedule_dates';
    protected $fillable = [
        'employee_schedule_id',
        'date',
        'outlet_schedule_shift_id'
    ];

    public function employee_schedule(): BelongsTo
    {
        return $this->belongsTo(EmployeeSchedule::class);
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(EmployeeAttendance::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(OutletScheduleShift::class, 'outlet_schedule_shift_id', 'id');
    }

    protected static function newFactory()
    {
        return \Modules\EmployeeSchedule\Database\factories\EmployeeScheduleDateFactory::new();
    }
}
