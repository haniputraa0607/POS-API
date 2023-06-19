<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;
    protected $primaryKey = 'id_setting';

	protected $fillable = [
		'key',
		'value',
		'value_text'
	];

}
