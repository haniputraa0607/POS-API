<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\User\Entities\User;

class Feature extends Model
{
    use HasFactory;

     protected $fillable = [
        'feature_type',
        'feature_module',
        'show_hide',
        'order'
     ];

     public function users()
     {
         return $this->belongsToMany(User::class);
     }
}
