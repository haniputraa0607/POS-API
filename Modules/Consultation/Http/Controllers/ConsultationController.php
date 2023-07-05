<?php

namespace Modules\Consultation\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Modules\EmployeeSchedule\Entities\EmployeeSchedule;

class ConsultationController extends Controller
{

    public function mine(): JsonResponse
    {
        $data = EmployeeSchedule::with(['user','consultations.queue'])->whereRelation('consultations', 'user_id', Auth::user()->id)->get();
        return $this->ok('success', [$data]);
    }
    public function mineToday(Request $request): JsonResponse
    {
        $data = EmployeeSchedule::with(['user','consultations.queue'])->whereRelation('consultations', 'user_id', Auth::user()->id)->whereDate('date', $request->date ?? Carbon::today())->first();
        return $this->ok('success',$data);
    }

    public function index(): JsonResponse
    {
        return $this->ok('success', []);
    }

    public function store(Request $request): JsonResponse
    {
        return $this->ok('success', []);
    }

    public function show($id): JsonResponse
    {
        return $this->ok('success', []);
    }

    public function update(Request $request, $id): JsonResponse
    {
        return $this->ok('success', []);
    }

    public function destroy($id): JsonResponse
    {
        return $this->ok('success', []);
    }
}
