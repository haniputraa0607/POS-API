<?php

namespace Modules\Doctor\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Doctor\Database\factories\DoctorDayFactory;
use Modules\User\Entities\User;

class DoctorShift extends Model
{
    use HasFactory;
    public const DAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

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
        return DoctorDayFactory::new();
    }
}
