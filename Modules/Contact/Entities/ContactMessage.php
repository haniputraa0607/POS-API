<?php

namespace Modules\Contact\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactMessage extends Model
{
    use HasFactory;

    protected $table = 'contact_messages';
    protected $fillable = [
        'name',
        'email',
        'message'
    ];

}
