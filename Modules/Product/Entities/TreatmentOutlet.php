<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Outlet\Entities\TreatmentRoom;

class TreatmentOutlet extends Model
{
    use HasFactory;

    protected $table = 'treatment_outlets';
    protected $fillable = [
        'treatment_id',
        'outlet_id',
        'is_active',
        'treatment_room_id'
    ];

    public function treatment_room(): BelongsTo
    {
        return $this->belongsTo(TreatmentRoom::class);
    }
}
