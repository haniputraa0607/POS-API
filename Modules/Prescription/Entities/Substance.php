<?php

namespace Modules\Prescription\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Prescription\Entities\SubstanceOutletPrice;
use Modules\Prescription\Entities\CategorySubstance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Substance extends Model
{
    use HasFactory;

    protected $table = 'substances';
    protected $fillable = [
        'substance_name',
        'type',
        'unit',
        'price',
    ];

    public function outlet_price(): HasMany
    {
        return $this->hasMany(SubstanceOutletPrice::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(CategorySubstance::class);
    }
}
