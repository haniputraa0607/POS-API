<?php

namespace Modules\Doctor\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Doctor\Entities\BeauticianSchedule;

class BeauticianScheduleDate extends Model
{
    use HasFactory;

    protected $table = 'beautician_schedule_dates';

    protected $fillable = [
        'beautician_schedule_id',
        'date'
    ];

    public function beautician_schedule(): BelongsTo
    {
        return $this->belongsTo(BeauticianSchedule::class);
    }
}
