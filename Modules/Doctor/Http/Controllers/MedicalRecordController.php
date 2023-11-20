<?php

namespace Modules\Doctor\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Lib\MyHelper;
use Illuminate\Http\JsonResponse;
use Modules\User\Entities\User;
use Illuminate\Support\Facades\DB;
use Modules\Customer\Entities\Customer;
use DateTime;
use Modules\Consultation\Entities\Consultation;
use Modules\Customer\Entities\CategoryAllergy;
use Modules\Customer\Entities\CustomerAllergy;
use Modules\Doctor\Entities\TreatmentRecord;
use Modules\Doctor\Entities\TreatmentRecordType;
use Modules\Grievance\Entities\Grievance;
use Modules\Order\Entities\OrderConsultation;
use Modules\Order\Entities\OrderProduct;
use Modules\PatientGrievance\Entities\PatientGrievance;
use Modules\Product\Entities\Product;
use Modules\Product\Entities\ProductCategory;

class MedicalRecordController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function patientData(Request $request):mixed
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

        $customer = Customer::whereHas('orders', function($order) use($post){
            $order->where('id', $post['id_order']);
        })->first();

        if(!$customer){
            return $this->error('Customer not found');
        }

        $bithdayDate = new DateTime($customer['birth_date']);
        $now = new DateTime();
        $interval = $now->diff($bithdayDate)->y;

        $response = [
            'id'    => $customer['id'],
            'name'  => $customer['name'],
            'gender'  => $customer['gender'],
            'birth_date_text' => date('d F Y', strtotime($customer['birth_date'])),
            'birth_place' => $customer['birth_place'],
            'address' => $customer['address'] ?? null,
            'email' => $customer['email'],
            'phone' => $customer['phone'],
            'age'   => $interval,
            'joined' => date('d F Y', strtotime($customer['created_at'])),
            'last_edited' => date('d F Y', strtotime($customer['updated_at'])),
        ];

        return $this->ok('success', $response);

    }

    public function updatePatientData(Request $request):mixed
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

        $customer = Customer::where('id', $post['id'])->first();
        if(!$customer){
            return $this->error('Customer not found');
        }
        $update = $customer->update([
            'name' => $post['name'],
            'gender' => $post['gender'],
            'birth_date' => $post['birth_date'],
            'birth_place' => $post['birth_place'],
            'email' => $post['email'],
            'phone' => $post['phone'],
        ]);

        if(!$update){
            return $this->error('Failed to update data');
        }else{
            return $this->ok('success', []);
        }

    }

    public function allergy(Request $request): mixed
    {
        $cashier = $request->user();
        $outlet = $cashier->outlet;

        $category_allergies = CategoryAllergy::with(['allergies' => function($allergies){
            $allergies->select('id', 'category_id', 'name');
        }])->select('id', 'category_name')
        ->get()->toArray();

        return $this->ok('success', $category_allergies);

    }

    public function patientAllergy(Request $request):mixed
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

        $customer = Customer::with(['customer_allergies.allergy.category'])->whereHas('orders', function($order) use($post){
            $order->where('id', $post['id_order']);
        })->first();

        if(!$customer){
            return $this->error('Customer not found');
        }

        $allergies = [];
        foreach($customer['customer_allergies'] ?? [] as $customer_allergy){
            $allergies[] = [
                'id' => $customer_allergy['id'],
                'category_allergy_name' => $customer_allergy['allergy']['category']['category_name'],
                'allergy_name' => $customer_allergy['allergy']['name'],
                'notes' => $customer_allergy['notes'],
            ];
        }

        $response = [
            'id'    => $customer['id'],
            'name'  => $customer['name'],
            'is_allergy'  => $customer['is_allergy'],
            'allergies' => $allergies,
        ];

        return $this->ok('success', $response);

    }

    public function updatePatientAllergy(Request $request):mixed
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

        $customer = Customer::where('id', $post['id'])->first();
        if(!$customer){
            return $this->error('Customer not found');
        }

        $update = $customer->update([
            'is_allergy' => $post['is_allergy'],
        ]);

        if($post['is_allergy'] == 1 && isset($post['allergies'])){
            foreach($post['allergies'] ?? [] as $allergy){
                $customer_allergies[] = [
                    'allergy_id' => $allergy['id'],
                    'customer_id' => $customer['id'],
                    'notes' => $allergy['notes'] ?? null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
            }

            $delete_customer_allergies = CustomerAllergy::where('customer_id', $customer['id'])->delete();
            if($delete_customer_allergies){
                $insert_customer_allergies = CustomerAllergy::insert($customer_allergies);
            }
        }

        if(!$update){
            return $this->error('Failed to update data');
        }else{
            return $this->ok('success', []);
        }
    }

    public function treatmentRecordType(Request $request): JsonResponse
    {
        $return = [];
        $make_new = false;
        $file_path = storage_path('json/treatment_record_type.json');

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
            $treatment_record_type = TreatmentRecordType::select('id', 'name')->orderBy('name', 'asc')->get()->toArray();
            if ($treatment_record_type) {
                $config = [
                    'updated_at' => date('Y-m-d H:i'),
                    'data' => $treatment_record_type
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

    public function productCategory(Request $request)
    {
        $return = [];
        $make_new = false;
        $file_path = storage_path('json/product_category.json');

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
            $product_category = ProductCategory::select('id', 'product_category_name')->orderBy('product_category_name', 'asc')->get()->toArray();
            if ($product_category) {
                $config = [
                    'updated_at' => date('Y-m-d H:i'),
                    'data' => $product_category
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

    public function product(Request $request)
    {
        $return = [];
        $make_new = false;
        $file_path = storage_path('json/product.json');

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
            $product_category = Product::select('id','product_category_id', 'product_name')->orderBy('product_name', 'asc')->get()->toArray();
            if ($product_category) {
                $config = [
                    'updated_at' => date('Y-m-d H:i'),
                    'data' => $product_category
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

    public function medicalHistory(Request $request)
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
        $customer = Customer::whereHas('orders', function($order) use($post){
            $order->where('id', $post['id_order']);
        })->first();
        if(!$customer){
            return $this->error('Customer not found');
        }
        $histories = PatientGrievance::with('grievance')->whereHas('consultation', function($consultation) use ($customer, $post){
            $consultation->whereHas('order_consultation', function($order_consultation) use ($customer, $post){
                $order_consultation->whereHas('order', function($order) use ($customer, $post){
                    $order->where('patient_id', $customer->id)->whereNot('id', $post['id_order']);
                });
            });
        })->get();
        $history_dates = [];
        foreach($histories as $history){
            $date_arr = explode(' ', $history['created_at']);
            if(!in_array($date_arr[0], $history_dates)){
                $history_dates[] = $date_arr[0];
            }
        }
        foreach($history_dates as $date){
            $history_res = [];
            foreach($histories as $history){
                $date_arr = explode(' ', $history->created_at);
                if($date_arr[0] == $date){
                    $history_res[] = [
                        'id' => $history->grievance_id, 
                        'grievance' => $history->grievance['grievance_name'],
                        'notes' =>  $history->notes,
                    ];
                }
            }
            $history_arr[] = [
                'date' => $date,
                'grievance' => $history_res
            ];
        }
        $grievance_order = PatientGrievance::with('grievance')->whereHas('consultation', function($consultation) use ($customer, $post){
            $consultation->whereHas('order_consultation', function($order_consultation) use ($customer, $post){
                $order_consultation->whereHas('order', function($order) use ($customer, $post){
                    $order->where('id', $post['id_order']);
                });
            });
        })->get();
        $grievance_arr = [];
        foreach($grievance_order as $grievance){
            $grievance_arr[] = [
                'id' => $grievance['grievance']['id'], 
                'grievance' => $grievance['grievance']['grievance_name'],
                'notes' => $grievance->notes
            ];
        }
        
        $treatmentRecord = TreatmentRecord::with('product', 'product_category')->whereHas('order', function($order) use ($customer, $post){
            $order->where('patient_id', $customer->id)->where('id', $post['id_order']);
        })->get();
        $treatmentNow = [];
        foreach($treatmentRecord as $treatment){
            $treatmentNow[] = [
                "type_id" => $treatment->treatment_record_type_id,
                "type_name" => $treatment->treatment_record_type['name'],
                'product_category_id' => $treatment->product_category_id ?? '',
                'product_category_name' => $treatment->product_category['product_category_name'],
                'product_id' => $treatment->product['id'] ?? '',
                'product_name' => $treatment->product['product_name'] ?? '',
                'notes' => $treatment->notes
            ];
        }

        $treatmentHistory = TreatmentRecord::with('treatment_record_type', 'product', 'product_category')->whereHas('order', function($order) use ($customer, $post){
            $order->where('patient_id', $customer->id)->whereNot('id', $post['id_order']);
        })->get();

        $treatment_history_dates = [];
        foreach($treatmentHistory as $treatment){
            $date_arr = explode(' ', $treatment['created_at']);
            if(!in_array($date_arr[0], $treatment_history_dates)){
                $treatment_history_dates[] = $date_arr[0];
            }
        }
        $treatment_history_arr = [];
        foreach($treatment_history_dates as $date){
            $history_res = [];
            foreach($treatmentHistory as $treatment){
                $date_arr = explode(' ', $treatment->created_at);
                if($date_arr[0] == $date){
                    $history_res[] = [
                        "type_id" => $treatment->treatment_record_type_id,
                        "type_name" => $treatment->treatment_record_type['name'],
                        'product_category_id' => $treatment->product_category_id ?? '',
                        'product_category_name' => $treatment->product_category['product_category_name'],
                        'product_id' => $treatment->product['id'] ?? '',
                        'product_name' => $treatment->product['product_name'] ?? '',
                        'notes' => $treatment->notes
                    ];
                }
            }
            $treatment_history_arr[] = [
                'date' => $date,
                'grievance' => $history_res
            ];
        }
        $response = [
            'id_order' => $post['id_order'],
            'grievance' => [
                'now' => $grievance_arr,
                'history' => $history_arr ?? []
            ],
            'treatment_or_therapy' => [
                'now' => $treatmentNow,
                'history' => $treatment_history_arr
            ]
        ];
        return $this->ok("success", $response);
    }

    public function updateMedicalHistory(Request $request)
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
        $customer = Customer::whereHas('orders', function($order) use($post){
            $order->where('id', $post['id_order']);
        })->first();
        if(!$customer){
            return $this->error('Customer not found');
        }

        $consultation = Consultation::whereHas('order_consultation', function($order_consultation) use ($customer, $post){
            $order_consultation->whereHas('order', function($order) use ($customer, $post){
                $order->where('patient_id', $customer->id)->where('id', $post['id_order']);
            });
        })->first();

        if($post['grievance']){
            $grievance_insert = [];
            foreach($post['grievance'] as $grievance){
                $grievance_data = Grievance::where('id', $grievance['id'])->first();
                $grievance_insert[] = [
                    'consultation_id' => $consultation['id'],
                    'grievance_id' => $grievance_data['id'],
                    'from_pos' => '0',
                    'notes' => $grievance['notes'],
                ];
            }
            $delete_patient_grievance = PatientGrievance::where('consultation_id', $consultation->id)->delete();
            if($grievance_insert){
                $insert_patient_grievance = PatientGrievance::insert($grievance_insert);
            }
        }

        if($post['treatment_or_therapy']){
            $treatment_record = [];
            foreach($post['treatment_or_therapy'] as $key){
                $treatment_record[] = [
                    'treatment_record_type_id' => $key['type_id'],
                    'order_id' => $post['id_order'],
                    'product_category_id' => $key['product_category_id'],
                    'product_id' => $key['product_id'],
                    'notes' => $key['notes'],
                ];
            }
            $delete_treatment_record = TreatmentRecord::where('order_id', $post['id_order'])->delete();
            if($delete_treatment_record){
                $treatment_record_insert = TreatmentRecord::insert($treatment_record);
            }
        }

        return $this->ok("success", $post);
    }
}
