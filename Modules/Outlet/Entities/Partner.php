<?php

namespace Modules\Outlet\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Outlet\Database\factories\PartnerFactory;

class Partner extends Model
{
    use HasFactory;

    protected $table = 'partners';
    protected $fillable = [
        'partner_code',
        'partner_name',
        'partner_email',
        'partner_phone',
        'partner_address'
    ];

    protected static function newFactory()
    {
        return PartnerFactory::new();
    }
}
