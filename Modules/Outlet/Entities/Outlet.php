<?php

namespace Modules\Outlet\Entities;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use KodePandai\Indonesia\Models\District;
use Modules\Outlet\Database\factories\OutletFactory;
use Modules\Outlet\Entities\OutletSchedule;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Outlet extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'outlets';
    protected $fillable = [
        'equal_id',
        'name',
        'id_partner',
        'partner_equal_id',
        'outlet_code',
        // 'id_city',
        'outlet_phone',
        'outlet_email',
        // 'outlet_latitude',
        // 'outlet_longitude',
        'status',
        'is_tax',
        'consultation_price',
        'address',
        'district_code',
        'postal_code',
        'coordinates',
        'activities',
        'images',
        'verified_at',
        'deleted_at'
    ];

    protected static function newFactory()
    {
        return OutletFactory::new();
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class, 'district_code', 'code');
    }

    public function outlet_schedule(): HasMany
    {
        return $this->hasMany(OutletSchedule::class);
    }

}
