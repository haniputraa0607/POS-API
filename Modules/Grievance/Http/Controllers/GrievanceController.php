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
use Modules\POS\Http\Controllers\POSController;

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
                'id_grievance' => $patient_grievance['grievance']['id'],
                'grievance_name' => $patient_grievance['grievance']['grievance_name'],
                'notes' => $patient_grievance['notes'] ?? null,
            ];
        }
        return $this->ok($message, $return);

    }

    public function show(Request $request): JsonResponse
    {
        $return = [];
        $make_new = false;
        $file_path = storage_path('json/grievances.json');

        if (!is_dir(dirname($file_path))) {
            mkdir(dirname($file_path), 0755, true);
        }

        if (file_exists($file_path)) {
            $config = json_decode(file_get_contents($file_path), true);
            $file_updated_time = strtotime($config['updated_at'] . ' +6 hours');

            if ($file_updated_time <= strtotime(date('Y-m-d H:i'))) {
                $make_new = true;
            }
        } else {
            $make_new = true;
        }

        if ($make_new) {
            $grievance = Grievance::select('id', 'grievance_name')->orderBy('grievance_name', 'asc')->get()->toArray();
            if ($grievance) {
                $config = [
                    'updated_at' => date('Y-m-d H:i'),
                    'data' => $grievance
                ];
                if (is_writable(dirname($file_path))) {
                    file_put_contents($file_path, json_encode($config));
                } else {
                    return $this->error("Tidak dapat menulis ke file JSON. Periksa izin direktori.");
                }
            }
        }
        $return = $config['data'] ?? [];
        return $this->ok("success", $return);
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

        $patient_grievance = PatientGrievance::with(['consultation.order_consultation'])->whereHas('consultation.order_consultation.order')->where('id', $post['id'])->first();
        if(!$patient_grievance){
            return $this->error('Grievance not found');
        }

        $delete = $patient_grievance->delete();
        if(!$delete){
            DB::rollBack();
            return $this->error('Failed to delete grievance patient');
        }

        DB::commit();
        return $this->getGrievance(['id_order' => $patient_grievance['consultation']['order_consultation']['order_id']],'Success to delete grievance patient');

    }

    public function addGrievancePatientPOS(Request $request): JsonResponse
    {
        $request->validate([
            'id_consultation' => 'required',
        ]);

        $cashier = $request->user();
        $outlet = $cashier->outlet;
        $post = $request->json()->all();


        if(!$outlet){
            return $this->error('Outlet not found');
        }

        $order_consul = OrderConsultation::with(['order'])->where('id', $post['id_consultation'])->first();
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

        $delete = PatientGrievance::where('consultation_id', $consultation['id'])->delete();
        $patient_grievance = [];
        foreach($post['grievances'] ?? [] as $key_gre => $gre){
            $patient_grievance[] = [
                'consultation_id' => $consultation['id'],
                'grievance_id'    => $gre['id'],
                'from_pos'        => 1,
                'notes'           => $gre['notes'],
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ];
        }

        $insert = PatientGrievance::insert($patient_grievance);
        if(!$insert){
            DB::rollBack();
            return $this->error('Grievance error');
        }

        DB::commit();
        return (new POSController)->getDataOrder(true, ['id_outlet' => $outlet['id'], 'id_customer' => $order_consul['order']['patient_id']], 'Success to add update grievances');

    }
}
