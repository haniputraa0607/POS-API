<?php

namespace Modules\Customer\Entities;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Customer\Database\factories\CustomerFactory;
use Modules\Customer\Entities\TreatmentPatient;
use Modules\Doctor\Entities\DoctorSuggestion;
use Modules\Customer\Entities\CustomerAllergy;

class Customer extends Model
{
    use HasFactory;

    public const GENDER = ['Male', 'Female'];
    protected $table = 'customers';
    protected $fillable = [
        'name',
        'gender',
        'birth_date',
        'birth_place',
        'phone',
        'email',
        'count_transaction',
        'last_transaction_id',
        'last_order_id',
        'is_active',
        'is_allergy'
    ];

    public function consulations(): HasMany
    {
        return $this->hasMany(consulations::class);
    }

    public function scopeIsActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function treatments(): HasMany
    {
        return $this->hasMany(TreatmentPatient::class, 'patient_id', 'id');
    }

    protected static function newFactory()
    {
        return CustomerFactory::new();
    }

    public function suggestions(): HasMany
    {
        return $this->hasMany(DoctorSuggestion::class, 'patient_id', 'id');
    }

    public function customer_allergies(): HasMany
    {
        return $this->hasMany(CustomerAllergy::class);
    }
}
