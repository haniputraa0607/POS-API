<?php

namespace Modules\User\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\User\Database\factories\AdminFactory;
use Modules\User\Entities\AdminFeature;

class Admin extends Model
{
    use HasFactory;

    protected $table = 'admins';
    protected $fillable = [
        'id',
        'name',
    ];

    public function admin_features(): HasMany
    {
        return $this->hasMany(AdminFeature::class, 'admin_id', 'id');
    }

    public function user() : BelongsTo {
        return $this->belongsTo(User::class, 'id', 'id');
    }

    protected static function newFactory()
    {
        return AdminFactory::new();
    }
}
