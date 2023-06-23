<?php

namespace Modules\Doctor\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Lib\MyHelper;

class DoctorController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function home(Request $request){

        $timezone = 7;
        $status_queue = 'VACANT';
        $status_queue = '2 QUEUE';
        $data = [
            'status_outlet' => true,
            'clock' => MyHelper::adjustTimezone(date('H:i'), $timezone, 'H:i', true),
            'status_queue' => $status_queue,
            'doctor' => [
                'id' => 1,
                'name' => 'dr. Karni Nasution',
            ],
        ];

        return $this->ok('', $data);

    }

    public function listService(Request $request){

        $data = [
            'service' => [
                'product' => true,
                'treatment' => true,
                'consultation' => true,
                'prescription' => true
            ]
            ];

        return $this->ok('', $data);

    }
}
