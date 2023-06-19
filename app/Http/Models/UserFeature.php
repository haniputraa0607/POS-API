<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserFeature extends Model
{
    use HasFactory;
    public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'id_user' => 'int',
		'id_feature' => 'int'
	];

	protected $fillable=[
		'id_feature',
		'id_user'
	];

	public function feature()
	{
		return $this->belongsTo(\App\Http\Models\Feature::class, 'id_feature');
	}

	public function user()
	{
		return $this->belongsTo(\Modules\User\Entities\User::class, 'id_user');
	}
}
