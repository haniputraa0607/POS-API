<?php

namespace Modules\Customer\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Model
{
    use HasFactory;

    protected $table = 'customers';
    protected $fillable = [
        'name',
        'gender',
        'birth_date',
        'phone',
        'email',
        'last_transaction',
        'count_transaction',
    ];
}
