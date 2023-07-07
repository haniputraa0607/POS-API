<?php

namespace Modules\EmployeeSchedule\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\EmployeeSchedule\Entities\EmployeeSchedule;

class EmployeeScheduleDate extends Model
{
    use HasFactory;

    protected $table = 'employee_schedule_dates';
    protected $fillable = [
        'employee_schedule_id',
        'date',
        'outlet_shift',
        'time_start',
        'time_end',
    ];

    public function employee_schedule(): BelongsTo
    {
        return $this->belongsTo(EmployeeSchedule::class);
    }

    protected static function newFactory()
    {
        return \Modules\EmployeeSchedule\Database\factories\EmployeeScheduleDateFactory::new();
    }
}
