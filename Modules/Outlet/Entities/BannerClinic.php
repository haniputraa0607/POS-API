<?php

namespace Modules\Outlet\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Outlet\Database\factories\PartnerFactory;

class BannerClinic extends Model
{
    use HasFactory;

    protected $table = 'banner_clinics';
    protected $fillable = [
        'image',
    ];
}
