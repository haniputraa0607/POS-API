<?php

namespace Modules\Outlet\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OutletDevice extends Model
{
    use HasFactory;

    protected $table = 'outlet_devices';
    protected $fillable = [
        'outlet_id',
        'date',
        'name',
        'device_id',
        'device_token',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function schedule_date(): BelongsTo
    {
        return $this->belongsTo(EmployeeScheduleDate::class);
    }
}
