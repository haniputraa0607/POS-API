<?php

namespace Modules\EmployeeSchedule\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Modules\EmployeeSchedule\Entities\EmployeeSchedule;
use Modules\EmployeeSchedule\Http\Requests\StoreRequest;
use Modules\EmployeeSchedule\Http\Requests\UpdateRequest;
use Modules\User\Entities\User;

class EmployeeScheduleController extends Controller
{

    public function index(Request $request): JsonResponse
    {
        $data = EmployeeSchedule::with('user.outlet.district')
        ->when($request->date , function ($query, $date) {
            return $query->whereDate('date', $date);
        })
        ->paginate(10);
        return $this->ok("success get all schedules", $data);
    }

    public function doctor(): JsonResponse
    {
        $data = EmployeeSchedule::with('user.outlet.district')->doctor()->paginate(10);
        return $this->ok("succes get all schedules of all doctors", $data);
    }

    public function doctorDetail(int $id): JsonResponse
    {
        $data = User::with(['schedules', 'outlet.district'])->doctor()->where('id', $id)->firstOrFail();
        return $this->ok("success get all schedules of doctor $data->name", $data);
    }

    public function cashier(): JsonResponse
    {
        $data = EmployeeSchedule::with('user.outlet.district')->cashier()->paginate(10);
        return $this->ok("succes get all schedules of all cashiers", $data);
    }

    public function cashierDetail(int $id): JsonResponse
    {
        $data = User::with(['schedules', 'outlet.district'])->cashier()->where('id', $id)->firstOrFail();
        return $this->ok("success get all schedules of cashier $data->name ", $data);
    }

    public function store(StoreRequest $request): JsonResponse
    {
        $data = EmployeeSchedule::create($request->all());
        return $this->ok("succes", $data);
    }

    public function show(EmployeeSchedule $schedule): JsonResponse
    {
        return $this->ok("succes", $schedule->load('user.outlet.district'));
    }

    public function update(UpdateRequest $request, EmployeeSchedule $schedule): JsonResponse
    {
        $schedule->update($request->all());
        return $this->ok("succes",  $schedule);
    }

    public function destroy(EmployeeSchedule $schedule): JsonResponse
    {
        $schedule->delete();
        return $this->ok("succes", $schedule);
    }

    public function mine(): JsonResponse
    {
        /**
         * @var User Auth::user()
         */
        return $this->ok("succes", Auth::user()->load(['schedules', 'outlet.district']));
    }
}
