<?php

namespace Modules\Outlet\Entities;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use KodePandai\Indonesia\Models\District;
use Modules\Outlet\Database\factories\OutletFactory;

class Outlet extends Model
{
    use HasFactory;

    protected $table = 'outlets';
    protected $fillable = [
        'name',
        'address',
        'district_code',
        'postal_code',
        'coordinates',
        'activities'
    ];
    
    protected static function newFactory()
    {
        return OutletFactory::new();
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class, 'district_code', 'code');
    }

}
