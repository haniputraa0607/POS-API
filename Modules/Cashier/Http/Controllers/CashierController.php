<?php

namespace Modules\Cashier\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Lib\MyHelper;
use Illuminate\Http\JsonResponse;
use Modules\User\Entities\User;
use Modules\Customer\Entities\Customer;
use DateTime;
use Modules\EmployeeSchedule\Entities\EmployeeScheduleDate;
use Modules\Cashier\Entities\EmployeeAttendance;
use Modules\Outlet\Entities\OutletSchedule;

class CashierController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function histories(Request $request):mixed
    {
        $cashier = $request->user();
        $outlet = $cashier->outlet;

        $schedules = EmployeeScheduleDate::with([
            'employee_schedule.user',
            'attendance.device',
            'shift'
        ])->whereHas('employee_schedule', function($schedule) use($outlet){
            $schedule->where('outlet_id', $outlet['id'])
            ->where('schedule_month', date('m'))
            ->where('schedule_year', date('Y'));
        })->whereHas('attendance')->whereDate('date', '<=',date('Y-m-d'))->get()->toArray();

        $data[] = [
            'date' => date('d F Y'),
            'is_today' => 1,
            'list' => []
        ];
        foreach($schedules ?? [] as $schedule){

            $detail = [];
            foreach($schedule['attendance'] as $attendance){
                $detail[] = [
                    'date' => $attendance['date'] == date('Y-m-d') ? 'Today - '.date('d F Y') : date('d F Y', strtotime($attendance['date'])),
                    'type' => $attendance['type'] == 'Log in' ? 'Entry' : 'Log out',
                    'time' => date('H:i', strtotime($attendance['attendance_time']))
                ];
            }
            if($schedule['shift'] && $schedule['shift']['start_break'] && $schedule['shift']['end_break']){
                $detail[] = [
                    'date' => date('Y-m-d', strtotime($schedule['date'])) == date('Y-m-d') ? 'Today - '.date('d F Y') : date('d F Y', strtotime($schedule['date'])),
                    'type' => 'Recess',
                    'time' => date('H:i', strtotime($schedule['shift']['start_break'])).' - '.date('H:i', strtotime($schedule['shift']['end_break']))
                ];
            }

            $value = [
                'type' => $attendance['device']['name'],
                'date' => date('Y-m-d', strtotime($schedule['date'])),
                'name' => $schedule['employee_schedule']['user']['name'],
                'shift' => $schedule['shift']['shift'],
                'start' => date('H:i', strtotime($schedule['shift']['shift_time_start'])),
                'end' => date('H:i', strtotime($schedule['shift']['shift_time_end'])),
                'text_shift' => date('H:i', strtotime($schedule['shift']['shift_time_start'])).' - '.date('H:i', strtotime($schedule['shift']['shift_time_end'])),
                'detail' => $detail
            ];

            $check = array_search(date('d F Y', strtotime($schedule['date'])), array_column($data??[], 'date'));
            if($check !== false){
                array_push($data[$check]['list'], $value);
            }else{
                $data[] = [
                    'date' => date('d F Y', strtotime($value['date'])),
                    'is_today' => 0,
                    'list' => [
                        $value
                    ]
                ];
            }
        }

        return $this->ok('Success to get histories', $data);
    }

    public function listCashier(Request $request): mixed
    {
        $cashier = $request->user();
        $outlet = $cashier->outlet;

        $make_new = false;
        $check_json = file_exists(storage_path() . "/json/list_cashier.json");
        if($check_json){
            $config = json_decode(file_get_contents(storage_path() . "/json/list_cashier.json"), true);
            if(isset($config[$outlet['id']])){
                if(date('Y-m-d H:i', strtotime($config[$outlet['id']]['updated_at']. ' +6 hours')) <= date('Y-m-d H:i')){
                    $make_new = true;
                }
            }else{
                $make_new = true;
            }
        }else{
            $make_new = true;
        }

        if($make_new){

            return $all = User::with(['employee_schedules' => function($employee_schedules){
                $employee_schedules->where('schedule_month', date('m'))
                ->where('schedule_year', date('Y'))
                ->with(['employee_schedule_dates' => function($employee_schedule_dates){
                    $employee_schedule_dates->whereDate('date', '<=',date('Y-m-d'));
                }]);
            }])->where('outlet_id', $outlet['id'])
            ->cashier()->isActive()->get()->toArray();

            $config[$outlet['id']] = [
                'updated_at' => date('Y-m-d H:i'),
                'data'       => $all
            ];
            file_put_contents(storage_path('/json/list_cashier.json'), json_encode($config));

        }
        $config = $config[$outlet['id']] ?? [];

        $cashiers = $config['data'] ?? [];

        $result = [];
        foreach($cashiers ?? [] as $val){
            $result[] = [
                'id' => $val['id'],
                'name' => $val['name'],
            ];
        }

        return $this->ok('Success to get cashiers', $result);

    }

    public function scheduleAll(Request $request): mixed
    {
        $post = $request->json()->all();
        $cashier = $request->user();
        $outlet = $cashier->outlet;

        if(!isset($post['date'])){
            return $this->error('Date cant be null');
        }

        $date = date('Y-m-d', strtotime($post['date']));
        if(date('m', strtotime($date)) == date('m')){
            $today_month = true;
        }

        $year = date('Y',strtotime($date));
        $month = date('m',strtotime($date));
        $today = date('d',strtotime($date));

        $dates = MyHelper::getListDate(1,$month,$year);
        $data = [];
        foreach($dates['list']  ?? [] as $date){

            $sche = OutletSchedule::with(['outlet_shifts.employee' => function($employee_sch) use($date){
                $employee_sch->whereDate('employee_schedule_dates.date', $date)->with(['employee_schedule.user']);
            }])->where('day', date('l', strtotime($date)))->where('outlet_id', $outlet['id'])
            ->first();
            if($sche){
                $result = [
                    'id' => $sche['id'],
                    'outlet_id' => $sche['outlet_id'],
                    'shifts' => array_map(function($outlet_shift){
                        $return_outlet_shift['shift_name'] = $outlet_shift['shift'];
                        $return_outlet_shift['start'] = date('H:i', strtotime($outlet_shift['shift_time_start']));
                        $return_outlet_shift['end'] = date('H:i', strtotime($outlet_shift['shift_time_end']));
                        $return_outlet_shift['text_shift'] = date('H:i', strtotime($outlet_shift['shift_time_start'])).' - '.date('H:i', strtotime($outlet_shift['shift_time_end']));
                        $return_outlet_shift['cashiers'] = array_map(function($employees){
                            $return_employee['id'] = $employees['employee_schedule']['user']['id'];
                            $return_employee['name'] = $employees['employee_schedule']['user']['name'];
                            return $return_employee;
                        },$outlet_shift['employee'] ?? []);
                        return $return_outlet_shift;
                    }, $sche['outlet_shifts']->toArray() ?? [])
                ];
            }
            $data[] = [
                'date' => $date,
                'date_text' => date('d F Y', strtotime($date)),
                'data' => $result ?? null
            ];
        }

        return $this->ok('Success to get all schedule', $data);
    }
}
