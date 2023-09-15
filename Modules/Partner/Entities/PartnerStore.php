<?php

namespace Modules\Partner\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Partner\Entities\PartnerEqual;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Partner\Entities\PartnerSosialMedia;

class PartnerStore extends Model
{
    use HasFactory;

    protected $table = 'partner_stores';
    protected $fillable = [
        'partner_equal_id',
        'equal_id',
        'store_name',
        'store_address',
        'store_city',
    ];

    public function partnerEqual(): BelongsTo
    {
        return $this->belongsTo(PartnerEqual::class);
    }

    public function partner_sosial_media(): HasMany
    {
        return $this->hasMany(PartnerSosialMedia::class);
    }

}
