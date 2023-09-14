<?php

namespace Modules\Partner\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Partner\Entities\PartnerEqual;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficialPartnerDetail extends Model
{
    use HasFactory;

    protected $table = 'official_partner_details';
    protected $fillable = [
        'title',
        'description',
        'link',
    ];

}
