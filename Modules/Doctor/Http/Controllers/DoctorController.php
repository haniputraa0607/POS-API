<?php

namespace Modules\Doctor\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Lib\MyHelper;
use Illuminate\Http\JsonResponse;
use Modules\Outlet\Http\Controllers\OutletController;
use Modules\User\Entities\User;
use Modules\Doctor\Entities\DoctorShift;

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

    public function getDoctor(Request $request):mixed
    {
        $post = $request->json()->all();
        $cashier = $request->user();
        $outlet =  $cashier->outlet;

        if(!$outlet){
            return $this->error('Outlet not found');
        }

        if(!isset($post['search']) || !isset($post['search']['filter'])){
            return $this->error('Filter cant be null');
        }

        $date = date('Y-m-d');
        if($post['search']['filter'] == 'date'){
            $date = date('Y-m-d', strtotime($post['search']['value']));
        }

        $doctors = User::with(['doctor_schedules.schedule_dates' => function($query) use($date){
            $query->where('doctor_schedule_dates.date', $date);
        }])
        ->whereHas('doctor_schedules.schedule_dates',function($query) use($date){
            $query->where('schedule_month', date('m', strtotime($date)));
            $query->where('schedule_year', date('Y', strtotime($date)));
            $query->where('doctor_schedule_dates.date', $date);
        });

        if($post['search']['filter'] == 'name'){
            $doctors = $doctors->where('name', 'like', '%'.$post['search']['value'].'%');
        }

        $doctors = $doctors->doctor()->get()->toArray();

        $list_doctors = [];
        foreach($doctors ?? [] as $key => $doc){

            $day = date('l', strtotime($date));
            $shifts = DoctorShift::where('user_id', $doc['id'])->where('day',$day)->get()->toArray();

            $doc_shift = [];
            foreach($shifts ?? [] as $key_2 =>$shift){
                $doc_shift[] = [
                    'id_doctor_shift' => $shift['id'],
                    'time'            => date('H:i',strtotime($shift['start'])).' - '.date('H:i',strtotime($shift['end'])),
                    'quote'           => $shift['quota']
                ];
            }

            if(isset($doc['doctor_schedules']) && !empty($doc_shift)){
                $list_doctors[] = [
                    'id_doctor' => $doc['id'],
                    'name'      => $doc['name'],
                    'date'      => date('j F Y',strtotime($date)),
                    'photo'     => $doc['photo'] ?? null,
                    'shifts'    => $doc_shift
                ];
            }
        }
        $return = [
            'available' => count($list_doctors),
            'list_doctors' => $list_doctors,
        ];
        return $this->ok('', $return);

    }
}
