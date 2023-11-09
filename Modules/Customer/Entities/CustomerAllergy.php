<?php

namespace Modules\Customer\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Customer\Entities\Allergy;
use Modules\Customer\Entities\Customer;

class CustomerAllergy extends Model
{
    use HasFactory;

    protected $table = 'customer_allergies';
    protected $fillable = [
        'allergy_id',
        'customer_id',
        'notes'
    ];

    public function allergy(): BelongsTo
    {
        return $this->belongsTo(Allergy::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
