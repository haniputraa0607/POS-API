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

class OfficialPartnerHome extends Model
{
    use HasFactory;

    protected $table = 'official_partner_home';
    protected $fillable = [
        'partner_equal_id',
    ];

    public function partner_equal(): BelongsTo
    {
        return $this->belongsTo(PartnerEqual::class);
    }

}
