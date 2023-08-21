<?php

namespace Modules\Prescription\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Prescription\Entities\Substance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CategorySubstance extends Model
{
    use HasFactory;

    protected $table = 'category_substances';
    protected $fillable = [
        'prescription_category_id',
        'substance_id',
    ];

    public function substance(): BelongsTo
    {
        return $this->belongsTo(Substance::class);
    }
}
