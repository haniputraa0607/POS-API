<?php

namespace Modules\Customer\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Customer\Entities\Allergy;

class CategoryAllergy extends Model
{
    use HasFactory;

    protected $table = 'category_allergies';
    protected $fillable = [
        'category_name',
    ];

    public function allergies(): HasMany
    {
        return $this->hasMany(Allergy::class);
    }

}
