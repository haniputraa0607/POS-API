<?php

namespace Modules\Outlet\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Outlet\Entities\Outlet;
use Modules\Outlet\Entities\OutletScheduleShift;

class OutletSchedule extends Model
{
    use HasFactory;

    protected $table = 'outlet_schedules';
    protected $fillable = [
        'outlet_id',
        'day',
        'open',
        'close',
        'is_closed',
    ];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function outlet_shifts(): HasMany
    {
        return $this->hasMany(OutletScheduleShift::class);
    }

    protected static function newFactory()
    {
        return \Modules\Outlet\Database\factories\OutletScheduleFactory::new();
    }
}
