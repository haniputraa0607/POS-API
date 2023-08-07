<?php

namespace Modules\Grievance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Grievance\Entities\Grievance;
use Modules\Grievance\Http\Requests\GrievanceRequest;
use Modules\PatientGrievance\Entities\PatientGrievance;
use Modules\Order\Entities\OrderConsultation;
use Modules\Order\Entities\Order;
use Modules\Order\Entities\OrderProduct;
use Illuminate\Support\Facades\DB;
use Modules\Consultation\Entities\Consultation;
use Illuminate\Support\Facades\Validator;

class GrievanceController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function index(Request $request): JsonResponse
    {
        $data = Grievance::isActive()->paginate($request->length ?? 10);
        return $this->ok("success", $data);
    }

    public function store(GrievanceRequest $request): JsonResponse
    {
        $grievance = Grievance::create($request->all());
        return $this->ok("success", $grievance);
    }

    public function update(GrievanceRequest $request, Grievance $grievance): JsonResponse
    {
        $grievance->update($request->all());
        return $this->ok("success", $grievance);
    }
    public function destroy(Grievance $grievance): JsonResponse
    {
        $grievance->delete();
        return $this->ok("success", $grievance);
    }

    public function getOrderGrievance(Request $request): JsonResponse
    {
        $request->validate([
            'id_order' => 'required'
        ]);

        $doctor = $request->user();
        $outlet = $doctor->outlet;
        $post = $request->json()->all();

        if(!$outlet){
            return $this->error('Outlet not found');
        }

        return $this->getGrievance(['id_order' => $post['id_order']],'');

    }

    public function getGrievance($data, $message): JsonResponse
    {
        $id_order = $data['id_order'];

        $return = [];
        $patient_grievances = PatientGrievance::with(['grievance'])->whereHas('consultation.order_consultation', function($consul) use($id_order){
            $consul->where('order_id', $id_order);
        })->get()->toArray();

        foreach($patient_grievances ?? [] as $key => $patient_grievance){
            $return[] = [
                'id' => $patient_grievance['id'],
                'grievance_name' => $patient_grievance['grievance']['grievance_name'],
                'notes' => $patient_grievance['notes'] ?? $patient_grievance['grievance']['description'],
            ];
        }
        return $this->ok($message, $return);

    }

    public function show(Request $request): JsonResponse
    {
        $grievance = Grievance::select('id', 'grievance_name')->orderBy('grievance_name', 'asc')->get()->toArray();
        return $this->ok("success", $grievance);
    }

    public function addGrievancePatient(Request $request): JsonResponse
    {
        $request->validate([
            'id_order' => 'required',
            'id_grievance' => 'required'
        ]);

        $doctor = $request->user();
        $outlet = $doctor->outlet;
        $post = $request->json()->all();


        if(!$outlet){
            return $this->error('Outlet not found');
        }

        $order_consul = OrderConsultation::where('order_id', $post['id_order'])->firstOrFail();

        DB::beginTransaction();
        $consultation = Consultation::where('order_consultation_id', $order_consul['id'])->first();
        if(!$consultation){
            $consultation = Consultation::create([
                'order_consultation_id' => $order_consul['id'],
            ]);
        }

        $add_grievance = PatientGrievance::updateOrCreate([
            'consultation_id' => $consultation['id'],
            'grievance_id' => $post['id_grievance'],
        ],[
            'notes' => $post['notes'] ?? null,
        ]);

        if(!$add_grievance){
            DB::rollBack();
            return $this->error('Failed to create grievance patient');
        }

        DB::commit();
        return $this->getGrievance(['id_order' => $post['id_order']],'Success to add grievance patient');

    }

    public function deleteGrievancePatient(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required',
        ]);

        $doctor = $request->user();
        $outlet = $doctor->outlet;
        $post = $request->json()->all();


        if(!$outlet){
            return $this->error('Outlet not found');
        }


        DB::beginTransaction();

        $patient_grievance = PatientGrievance::with(['consultation.order_consultation'])->whereHas('consultation.order_consultation.order')->where('id', $post['id'])->firstOrfail();

        $delete = $patient_grievance->delete();
        if(!$delete){
            DB::rollBack();
            return $this->error('Failed to delete grievance patient');
        }

        DB::commit();
        return $this->getGrievance(['id_order' => $patient_grievance['consultation']['order_consultation']['order_id']],'Success to delete grievance patient');

    }
}
