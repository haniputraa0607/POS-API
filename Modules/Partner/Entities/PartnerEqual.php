<?php

namespace Modules\Partner\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PartnerEqual extends Model
{
    use HasFactory;
    protected $table = 'partner_equals';
    protected $fillable = [
        'equal_id',
        'name',
        'email',
        'phone',
        'id_member',
        'is_suspended',
    ];
}
