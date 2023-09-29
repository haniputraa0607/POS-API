<?php

namespace Modules\Contact\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactSosialMedia extends Model
{
    use HasFactory;

    protected $table = 'contact_sosial_medias';
    protected $fillable = [
        'type',
        'username',
        'link'
    ];

}
