<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feature extends Model
{
    use HasFactory;
    protected $primaryKey = 'id_feature';

	protected $fillable = [
		'feature_type',
		'feature_module',
        'show_hide',
        'order'
	];

	public function users()
	{
		return $this->belongsToMany(\Modules\User\Entities\User::class, 'user_features', 'id_feature', 'id_user');
	}
}
