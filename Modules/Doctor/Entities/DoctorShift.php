<?php

namespace Modules\Doctor\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\User\Entities\User;

class DoctorShift extends Model
{
    use HasFactory;

    protected $table = 'doctor_shifts';
    protected $fillable = [
        'user_id',
        'day',
        'name',
        'start',
        'end',
        'price'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function newFactory()
    {
        return \Modules\Doctor\Database\factories\DoctorDayFactory::new();
    }
}
