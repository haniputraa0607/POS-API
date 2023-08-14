<?php

namespace Modules\Prescription\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PrescriptionOutletLog extends Model
{
    use HasFactory;

    protected $table = 'prescription_outlet_logs';
    protected $fillable = [
        'prescription_outlet_id',
        'qty',
        'stock_before',
        'stock_after',
        'source',
        'description',
    ];
}
