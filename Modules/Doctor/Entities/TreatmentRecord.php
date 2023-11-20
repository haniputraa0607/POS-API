<?php

namespace Modules\Doctor\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Order\Entities\Order;
use Modules\Product\Entities\Product;
use Modules\Product\Entities\ProductCategory;

class TreatmentRecord extends Model
{
    use HasFactory;

    protected $table = 'treatment_records';

    protected $fillable = [
        'treatment_record_type_id',
        'order_id',
        'product_category_id',
        'product_id',
        'notes',
    ];
    
    public function treatment_record_type()
    {
        return $this->belongsTo(TreatmentRecordType::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function product_category()
    {
        return $this->belongsTo(ProductCategory::class);
    }

}
