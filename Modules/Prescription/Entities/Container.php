<?php

namespace Modules\Prescription\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Prescription\Entities\ContainerOutletPrice;
use Modules\Prescription\Entities\CategoryContainer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Container extends Model
{
    use HasFactory;

    protected $table = 'containers';
    protected $fillable = [
        'container_name',
        'type',
        'unit',
        'price',
    ];

    public function outlet_price(): HasMany
    {
        return $this->hasMany(ContainerOutletPrice::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(CategoryContainer::class);
    }
}
