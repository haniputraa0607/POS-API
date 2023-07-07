<?php

namespace Modules\EmployeeSchedule\Entities;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
// use Modules\Consultation\Entities\Consultation;
use Modules\EmployeeSchedule\Database\factories\EmployeeScheduleFactory;
use Modules\User\Entities\User;
use Modules\EmployeeSchedule\Entities\EmployeeScheduleDate;
use Modules\Outlet\Entities\Outlet;

class EmployeeSchedule extends Model
{
    use HasFactory;

    protected $table = 'employee_schedules';
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
    public function employee_schedule_dates(): HasMany
    {
        return $this->hasMany(EmployeeScheduleDate::class);
    }
    public function scopeDoctor(Builder $query): Builder
    {
        return $query->whereRelation('user', 'type', 'salesman');
    }
    public function scopeDoctorDetail(Builder $query, $id): Builder
    {
        return $query->whereRelation('user', 'type', 'salesman')->whereRelation('user', 'id', $id);
    }
    public function scopeCashier(Builder $query): Builder
    {
        return $query->whereRelation('user', 'type', 'cashier');
    }
    public function scopeCashierDetail(Builder $query, $id): Builder
    {
        return $query->whereRelation('user', 'type', 'cashier')->whereRelation('user', 'id', $id);
    }

    protected static function newFactory()
    {
        return EmployeeScheduleFactory::new();
    }
}
