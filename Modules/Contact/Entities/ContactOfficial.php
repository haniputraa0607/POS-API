<?php

namespace Modules\Contact\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactOfficial extends Model
{
    use HasFactory;

    protected $table = 'contact_official';
    protected $fillable = [
        'official_name',
        'official_value'
    ];

}
