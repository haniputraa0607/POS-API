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
}
