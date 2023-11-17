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
use Modules\Customer\Entities\CategoryAllergy;
use Modules\Customer\Entities\CustomerAllergy;

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
}
