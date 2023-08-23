<?php

namespace Modules\Prescription\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Prescription\Entities\Substance;

class PrescriptionSubstance extends Model
{
    use HasFactory;

    protected $table = 'prescription_substances';
    protected $fillable = [
        'prescription_id',
        'substance_id',
        'qty'
    ];

    public function substance(): BelongsTo
    {
        return $this->belongsTo(Substance::class);
    }
}
