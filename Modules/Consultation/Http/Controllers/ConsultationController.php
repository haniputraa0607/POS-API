<?php

namespace Modules\Consultation\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Modules\EmployeeSchedule\Entities\EmployeeSchedule;
use Illuminate\Support\Facades\DB;
use Modules\Consultation\Entities\Consultation;
use Illuminate\Support\Facades\Validator;
use Modules\Order\Entities\OrderConsultation;
use Modules\Order\Entities\Order;
use Modules\Doctor\Http\Controllers\DoctorController;

class ConsultationController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

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

    public function submit(Request $request): JsonResponse
    {
        $request->validate([
            'id_order' => 'required',
        ]);

        $doctor = $request->user();
        $outlet = $doctor->outlet;
        $post = $request->json()->all();


        if(!$outlet){
            return $this->error('Outlet not found');
        }

        $order_consul = OrderConsultation::whereHas('consultation')->where('order_id', $post['id_order'])->firstOrFail();

        DB::beginTransaction();

        $consultation = Consultation::where('order_consultation_id', $order_consul['id'])->first();
        if(!$consultation){
            DB::rollBack();
            return $this->error('Failed to submit consultation');
        }

        $update = $consultation->update([
            'session_end' => 1,
            'treatment_recomendation' => $post['treatment_recommendation'] ?? null,
        ]);

        if(!$update){
            DB::rollBack();
            return $this->error('Failed to submit consultation');
        }

        DB::commit();
        return (new DoctorController)->getDataOrder([
            'order_id' => $post['id_order'],
            'order_consultation' => $order_consul
        ],'Success to submit consultation');

    }
}
