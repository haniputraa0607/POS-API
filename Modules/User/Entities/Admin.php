<?php

namespace Modules\User\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\User\Entities\AdminFeature;

class Admin extends Model
{
    use HasFactory;

    protected $table = 'admins';
    protected $fillable = [
        'name',
    ];

    public function admin_features(): HasMany
    {
        return $this->hasMany(AdminFeature::class);
    }
}
