<?php

namespace Modules\Partner\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Partner\Entities\PartnerEqual;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerSosialMedia extends Model
{
    use HasFactory;

    protected $table = 'partner_sosial_medias';
    protected $fillable = [
        'partner_store_id',
        'equal_id',
        'type',
        'url'
    ];

    public function partnerEqual(): BelongsTo
    {
        return $this->belongsTo(PartnerEqual::class);
    }

}
