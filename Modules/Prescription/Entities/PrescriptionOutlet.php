<?php

namespace Modules\Prescription\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PrescriptionOutlet extends Model
{
    use HasFactory;

    protected $table = 'prescription_outlets';
    protected $fillable = [
        'prescription_id',
        'outlet_id',
        'stock',
        'price'
    ];
}
