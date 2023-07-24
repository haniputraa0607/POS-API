<?php

namespace Modules\User\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\User\Entities\Admin;
use App\Http\Models\Feature;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\User\Database\factories\AdminFeatureFactory;

class AdminFeature extends Model
{
    use HasFactory;

    protected $table = 'admin_features';
    protected $fillable = [
        'admin_id',
        'feature_id',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id', 'id');
    }

    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class, 'feature_id', 'id');
    }

    protected static function newFactory()
    {
        return AdminFeatureFactory::new();
    }

}
