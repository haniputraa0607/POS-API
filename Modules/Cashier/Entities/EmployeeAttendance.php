<?php

namespace Modules\Cashier\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\EmployeeSchedule\Entities\EmployeeScheduleDate;
use Modules\User\Entities\User;
use Modules\Outlet\Entities\OutletDevice;

class EmployeeAttendance extends Model
{
    use HasFactory;

    protected $table = 'employee_attendances';
    protected $fillable = [
        'user_id',
        'date',
        'type',
        'employee_schedule_date_id',
        'attendance_time',
        'outlet_device_id'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function schedule_date(): BelongsTo
    {
        return $this->belongsTo(EmployeeScheduleDate::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(OutletDevice::class, 'outlet_device_id', 'id');
    }
}
