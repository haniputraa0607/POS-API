<?php

namespace Modules\Discount\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Consultation\Entities\Consultation;
use Modules\Outlet\Entities\Outlet;
use Modules\Product\Entities\Product;

class Discount extends Model
{
    use HasFactory;

    protected $table = 'discounts';

    protected $fillable = [
        'equal_id',
        'name',
        'description',
        'type',
        'mimimum_transaction_precentage',
        'mimimum_transaction_amount',
        'dicount_precentage',
        'dicount_amount',
        'expired_at',
        'verified_at'
    ];

    public function detail()
    {
        if ($this->type === 'product') {
            return $this->belongsToMany(Product::class, 'discount_products');
        } elseif ($this->type === 'consultation') {
            return $this->belongsToMany(Consultation::class, 'discount_consultations');
        } else {
            return null;
        }
    }


    public function outlet()
    {
        return $this->belongsToMany(Outlet::class, 'discount_outlets');
    }
    
    public function product()
    {
        return $this->belongsToMany(Product::class, 'discount_products');
    }

    public function consultation()
    {
        return $this->belongsToMany(Consultation::class, 'discount_consultations');
    }
    
}
