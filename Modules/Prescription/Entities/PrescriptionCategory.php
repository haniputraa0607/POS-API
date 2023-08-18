<?php

namespace Modules\Prescription\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PrescriptionCategory extends Model
{
    use HasFactory;

    protected $table = 'prescription_categories';
    protected $fillable = [
        'category_name',
    ];
}
