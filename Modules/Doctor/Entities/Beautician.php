<?php

namespace Modules\Doctor\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Doctor\Entities\BeauticianSchedule;
use Modules\Outlet\Entities\Outlet;

class Beautician extends Model
{
    use HasFactory;

    protected $table = 'beauticians';

    protected $fillable = [
        'outlet_id',
        'equal_id',
        'name',
        'email',
        'phone',
        'idc',
        'id_number',
        'birthdate',
        'image_url',
    ];

    public function beautician_schedules(): HasMany
    {
        return $this->hasMany(BeauticianSchedule::class);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }
}
