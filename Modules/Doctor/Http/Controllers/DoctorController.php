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
use Modules\Doctor\Entities\DoctorSchedule;
use Modules\Doctor\Entities\DoctorScheduleDate;
use Modules\Order\Entities\OrderConsultation;

class DoctorController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function home(Request $request){

        $doctor = $request->user();
        $outlet = $doctor->outlet;

        $status_outlet = true;
        $status_doctor = true;
        $order = [];
        $schedule = $outlet->outlet_schedule->where('day', date('l'))->first();
        if(!$schedule){
            $status_outlet = false;
        }elseif($schedule['is_closed'] == 1){
            $status_outlet = false;
        }elseif(($schedule['open'] && date('H:i') < date('H:i', strtotime($schedule['open']))) || ($schedule['close'] && date('H:i') > date('H:i', strtotime($schedule['close'])))){
            $status_outlet = false;
        }

        $schedule_doc = DoctorScheduleDate::whereHas('doctor_schedule', function($sch) use($doctor){
            $sch->where('user_id', $doctor['id']);
        })->whereDate('date', date('Y-m-d'))->first();
        if(!$schedule_doc){
            $status_doctor = false;
        }

        $shift = DoctorShift::where('user_id', $doctor['id'])->where('day', date('l'))->whereTime('start', '<=', date('H:i'))->whereTime('end', '>=', date('H:i'))->first();
        if(!$shift){
            $status_doctor = false;
        }

        $timezone = $outlet->district->province['timezone'] ?? 7;

        if($status_outlet && $status_doctor){
            $order = OrderConsultation::whereHas('order', function($order) use($outlet){
                $order->where('outlet_id', $outlet['id'])
                ->where('is_submited', 1)
                ->where('send_to_transaction', 0);
            })
            ->whereDate('schedule_date', date('Y-m-d'))
            ->where('doctor_id', $doctor['id'])
            ->get();
        }

        $data = [
            'status_outlet' => $status_outlet,
            'status_doctor' => $status_doctor,
            'clock' => MyHelper::adjustTimezone(date('H:i'), $timezone, 'H:i', true),
            'status_queue' => count($order) > 0 ? count($order).' QUEUE' : 'VACANT',
            'is_vacant' => count($order) > 0 ? false : true,
            'queue' => $order[0]['queue_code'],
            'doctor' => [
                'id' => $doctor['id'],
                'name' => $doctor['name'],
            ],
        ];

        return $this->ok('', $data);

    }

    public function listService(Request $request){

        $doctor = $request->user();
        $outlet = $doctor->outlet;

        if(!$outlet){
            return $this->error('Outlet not found');
        }

        $outlet_service = json_decode($outlet['activities'], true) ?? [];
        $data['service'] = [];

        foreach($outlet_service ?? [] as $key => $serv){

            $data['service'][] = [
                'icon' => 'tes',
                'icon_active' => 'tes',
                'title' => $serv == 'consultation' ? 'Overview' : ($serv == 'prescription' ? 'Prescription' : ($serv == 'product' ? 'Product' : ($serv == 'treatment' ? 'Treatment' : '')))
            ];

        }

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
        })
        ->where('outlet_id', $outlet['id']);

        if($post['search']['filter'] == 'name'){
            $doctors = $doctors->where('name', 'like', '%'.$post['search']['value'].'%');
        }

        $doctors = $doctors->doctor()->get()->toArray();

        $list_doctors = [];
        foreach($doctors ?? [] as $key => $doc){

            $day = date('l', strtotime($date));
            $shifts = DoctorShift::with(['order_consultations' => function($order_consultation) use($date){
                $order_consultation->whereDate('schedule_date', $date);
            }])->where('user_id', $doc['id'])->where('day',$day)->get()->toArray();

            $doc_shift = [];
            foreach($shifts ?? [] as $key_2 =>$shift){
                $doc_shift[] = [
                    'id_doctor_shift' => $shift['id'],
                    'time'            => date('H:i',strtotime($shift['start'])).' - '.date('H:i',strtotime($shift['end'])),
                    'quote'           => $shift['quota'] - count($shift['order_consultations'])
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
