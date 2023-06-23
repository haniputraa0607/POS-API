<?php

namespace Modules\POS\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class POSController extends Controller
{

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function home(Request $request){

        $data = [
            'status_outlet' => true,
            'queue' => [
                'product' => 'P06',
                'treatment' => 'T11',
                'consultation' => 'C08'
            ]
            ];

        return $this->ok('', $data);

    }

    public function listService(Request $request){

        $data = [
            'service' => [
                'product' => true,
                'treatment' => true,
                'consultation' => true
            ]
            ];

        return $this->ok('', $data);

    }
}
