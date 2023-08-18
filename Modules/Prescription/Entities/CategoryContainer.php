<?php

namespace Modules\Prescription\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Prescription\Entities\Container;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CategoryContainer extends Model
{
    use HasFactory;

    protected $table = 'category_containers';
    protected $fillable = [
        'prescription_category_id',
        'container_id',
    ];

    public function container(): BelongsTo
    {
        return $this->belongsTo(Container::class);
    }
}
