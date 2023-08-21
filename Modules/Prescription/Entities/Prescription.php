<?php

namespace Modules\Prescription\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Prescription\Entities\PrescriptionOutlet;
use Modules\Customer\Entities\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Prescription\Entities\PrescriptionCategory;
use Modules\Prescription\Entities\PrescriptionContainer;
use Modules\Prescription\Entities\PrescriptionSubstance;

class Prescription extends Model
{
    use HasFactory;

    protected $table = 'prescriptions';
    protected $fillable = [
        'prescription_code',
        'prescription_name',
        'type',
        'unit',
        'price',
        'is_custom',
        'patient_id',
        'prescription_category_id',
        'description',
        'is_active'
    ];

    public function prescription_outlets(): HasMany
    {
        return $this->hasMany(PrescriptionOutlet::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'patient_id', 'id');
    }

    public function scopeOriginal(Builder $query): Builder
    {
        return $query->whereNull('patient_id')->where('is_custom', 0);
    }

    public function scopeCustom(Builder $query): Builder
    {
        return $query->whereNotNull('patient_id')->where('is_custom', 1);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PrescriptionCategory::class, 'prescription_category_id');
    }

    public function prescription_container(): HasOne
    {
        return $this->hasOne(PrescriptionContainer::class);
    }

    public function prescription_substances(): HasMany
    {
        return $this->hasMany(PrescriptionSubstance::class);
    }
}
