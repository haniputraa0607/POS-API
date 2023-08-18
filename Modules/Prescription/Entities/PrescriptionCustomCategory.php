<?php

namespace Modules\Prescription\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PrescriptionCustomCategory extends Model
{
    use HasFactory;

    protected $table = 'prescription_custom_categories';
    protected $fillable = [
        'category_name',
    ];
}
