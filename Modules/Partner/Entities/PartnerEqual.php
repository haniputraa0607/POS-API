<?php

namespace Modules\Partner\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use KodePandai\Indonesia\Models\City;
use Modules\Partner\Entities\PartnerStore;

class PartnerEqual extends Model
{
    use HasFactory;
    protected $table = 'partner_equals';
    protected $fillable = [
        'equal_id',
        'name',
        'email',
        'phone',
        'type',
        'city_code',
        'image',
        'id_member',
        'is_suspended',
    ];

    protected $casts = [
        'city_code' => 'integer',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_code', 'code');
    }

    public function partner_store(): BelongsTo
    {
        return $this->belongsTo(PartnerStore::class, 'id', 'partner_equal_id');
    }

}
