<?php

namespace Modules\Prescription\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Prescription\Entities\Container;

class PrescriptionContainer extends Model
{
    use HasFactory;

    protected $table = 'prescription_containers';
    protected $fillable = [
        'prescription_id',
        'container_id',
    ];

    public function container(): BelongsTo
    {
        return $this->belongsTo(Container::class);
    }
}
