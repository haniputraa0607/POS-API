<?php

namespace Modules\Diagnostic\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Diagnostic\Entities\Diagnostic;
use Modules\Diagnostic\Http\Requests\DiagnosticRequest;
use Modules\PatientDiagnostic\Entities\PatientDiagnostic;
use Modules\Order\Entities\OrderConsultation;
use Modules\Order\Entities\Order;
use Modules\Order\Entities\OrderProduct;
use Illuminate\Support\Facades\DB;
use Modules\Consultation\Entities\Consultation;
use Illuminate\Support\Facades\Validator;

class DiagnosticController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function index(Request $request): JsonResponse
    {
        $data = Diagnostic::isActive()->paginate($request->length ?? 10);
        return $this->ok("success", $data);
    }

    public function store(DiagnosticRequest $request): JsonResponse
    {
        $diagnostic = Diagnostic::create($request->all());
        return $this->ok("success", $diagnostic);
    }

    public function update(DiagnosticRequest $request, Diagnostic $diagnostic): JsonResponse
    {
        $diagnostic->update($request->all());
        return $this->ok("success", $diagnostic);
    }

    public function destroy(Diagnostic $diagnostic): JsonResponse
    {
        $diagnostic->delete();
        return $this->ok("success", $diagnostic);
    }

    public function show(Request $request): JsonResponse
    {
        $diagnostic = Diagnostic::select('id', 'diagnostic_name')->orderBy('diagnostic_name', 'asc')->get()->toArray();
        return $this->ok("success", $diagnostic);
    }

    public function getOrderDiagnostic(Request $request): JsonResponse
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

        return $this->getDiagnostic(['id_order' => $post['id_order']],'');

    }

    public function getDiagnostic($data, $message): JsonResponse
    {
        $id_order = $data['id_order'];

        $return = [];
        $patient_diagnostics = PatientDiagnostic::with(['diagnostic'])->whereHas('consultation.order_consultation', function($consul) use($id_order){
            $consul->where('order_id', $id_order);
        })->get()->toArray();

        foreach($patient_diagnostics ?? [] as $key => $patient_diagnostic){
            $return[] = [
                'id' => $patient_diagnostic['id'],
                'diagnostic_name' => $patient_diagnostic['diagnostic']['diagnostic_name'],
                'notes' => $patient_diagnostic['notes'] ?? $patient_diagnostic['diagnostic']['description'],
            ];
        }
        return $this->ok($message, $return);

    }

    public function addDiagnosticPatient(Request $request): JsonResponse
    {
        $request->validate([
            'id_order' => 'required',
            'id_diagnostic' => 'required'
        ]);

        $doctor = $request->user();
        $outlet = $doctor->outlet;
        $post = $request->json()->all();


        if(!$outlet){
            return $this->error('Outlet not found');
        }

        $order_consul = OrderConsultation::where('order_id', $post['id_order'])->first();
        if(!$order_consul){
            return $this->error('Consultation not found');
        }

        DB::beginTransaction();
        $consultation = Consultation::where('order_consultation_id', $order_consul['id'])->first();
        if(!$consultation){
            $consultation = Consultation::create([
                'order_consultation_id' => $order_consul['id'],
            ]);
        }

        $add_diagnostic = PatientDiagnostic::updateOrCreate([
            'consultation_id' => $consultation['id'],
            'diagnostic_id' => $post['id_diagnostic'],
        ],[
            'notes' => $post['notes'] ?? null,
        ]);

        if(!$add_diagnostic){
            DB::rollBack();
            return $this->error('Failed to create diagnostic patient');
        }

        DB::commit();
        return $this->getDiagnostic(['id_order' => $post['id_order']],'Success to add diagnostic patient');

    }

    public function deleteDiagnosticPatient(Request $request): JsonResponse
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

        $patient_diagnostic = PatientDiagnostic::with(['consultation.order_consultation'])->whereHas('consultation.order_consultation.order')->where('id', $post['id'])->first();
        if(!$patient_diagnostic){
            return $this->error('Diagnostic not found');
        }

        $delete = $patient_diagnostic->delete();
        if(!$delete){
            DB::rollBack();
            return $this->error('Failed to delete diagnostic patient');
        }

        DB::commit();
        return $this->getDiagnostic(['id_order' => $patient_diagnostic['consultation']['order_consultation']['order_id']],'Success to delete diagnostic patient');

    }
}
