<?php

namespace Modules\User\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\User\Entities\Admin;
use App\Http\Models\Feature;

class AdminFeature extends Model
{
    use HasFactory;

    protected $table = 'admins';
    protected $fillable = [
        'admin_id',
        'feature_id',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class);
    }

}
