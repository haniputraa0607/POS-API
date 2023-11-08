<?php

namespace Modules\Doctor\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Doctor\Entities\NurseSchedule;

class NurseScheduleDate extends Model
{
    use HasFactory;

    protected $table = 'nurse_schedule_dates';

    protected $fillable = [
        'nurse_schedule_id',
        'date'
    ];

    public function nurse_schedule(): BelongsTo
    {
        return $this->belongsTo(NurseSchedule::class);
    }
}
