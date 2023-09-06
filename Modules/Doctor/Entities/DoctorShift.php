<?php

namespace Modules\Doctor\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Doctor\Database\factories\DoctorDayFactory;
use Modules\Order\Entities\OrderConsultation;
use Modules\User\Entities\User;

class DoctorShift extends Model
{
    use HasFactory;
    public const DAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    protected $table = 'doctor_shifts';
    protected $fillable = [
        'day',
        'name',
        'start',
        'end',
        'price'
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_has_shift', 'doctor_shift_id', 'user_id');
    }

    protected static function newFactory()
    {
        return DoctorDayFactory::new();
    }

    public function order_consultations(): HasMany
    {
        return $this->hasMany(OrderConsultation::class);
    }
}
