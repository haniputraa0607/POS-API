<?php

namespace Modules\Doctor\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SkinProblemRecord extends Model
{
    use HasFactory;

    protected $table = 'skin_problem_records';

    protected $fillable = [
        'patient_id', 
        'order_id',
        'name',
        'time_period',
        'tried_solution',
        'solution'
    ];
    
}
