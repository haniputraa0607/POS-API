<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\User\Entities\User;

class UserFeature extends Model
{
    use HasFactory;

    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'id_user' => 'int',
        'id_feature' => 'int'
    ];

    protected $fillable = [
        'id_feature',
        'id_user'
    ];

    public function feature()
    {
        return $this->belongsTo(Feature::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
