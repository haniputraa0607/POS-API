<?php

namespace Modules\Customer\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Customer\Entities\Customer;
use Modules\Customer\Http\Requests\Create;
use Modules\Customer\Http\Requests\Update;

class CustomerController extends Controller
{

    public function index(Request $request): JsonResponse
    {
        $data = Customer::paginate($request->length ?? 10);
        return $this->ok('success', $data);
    }

    public function store(Create $request): JsonResponse
    {
        $post = $request->json()->all();

        $customer = Customer::create($post);
        return $this->ok('success', $customer);
    }

    public function show(int $id): JsonResponse
    {
        $customer = Customer::isActive()->whereId($id)->firstOrFail();
        return $this->ok('success', $customer);
    }

    public function showByPhone(Request $request): JsonResponse
    {
        $post = $request->json()->all();

        $data = Customer::where('phone', (string)$post['phone'])->firstOrFail();
        return $this->ok('success', $data);
    }

    public function update(Update $request): mixed
    {
        $post = $request->json()->all();

        $customer = Customer::where('id', $post['id'])->first();
        if($customer){

            $request->validate([
                'phone' => 'required|unique:customers,phone,'.$customer['id'],
                'email' => 'required|email|unique:customers,email,'.$customer['id'],
            ]);

            $update = [
                "name"       => $post['name'],
                "gender"     => $post['gender'],
                "birth_date" => $post['birth_date'],
                "phone"      => $post['phone'],
                "email"      => $post['email'],
                "is_active"  => $post['is_active'],
            ];
            $customer->update($request->all());
            return $this->ok('success', $customer);
        }else{
            return $this->error('customer not found');
        }
    }

    public function destroy(Customer $customer): JsonResponse
    {
        $customer->delete();
        return $this->ok('success', $customer);
    }
}
