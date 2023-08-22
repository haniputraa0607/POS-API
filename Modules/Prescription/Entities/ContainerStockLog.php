<?php

namespace Modules\Prescription\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ContainerStockLog extends Model
{
    use HasFactory;

    protected $table = 'container_stock_logs';
    protected $fillable = [
        'container_stock_id',
        'qty',
        'stock_before',
        'stock_after',
        'source',
        'description',
    ];
}
