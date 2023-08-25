<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogCron extends Model
{
    protected $table = 'log_crons';
    protected $connection = 'mysql2';

    protected $fillable = [
        'cron',
        'status',
        'start_date',
        'end_date',
        'description'
    ];

    public function success($msg = null)
    {
        if (is_array($msg)) {
            $msg = json_encode($msg);
        }
        $this->status = 'success';
        $this->end_date = date('Y-m-d H:i:s');
        $this->description = $msg;
        $this->save();
    }

    public function fail($msg = null)
    {
        if (is_array($msg)) {
            $msg = json_encode($msg);
        }
        $this->status = 'fail';
        $this->end_date = date('Y-m-d H:i:s');
        $this->description = $msg;
        $this->save();
    }
}
