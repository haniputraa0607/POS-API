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
        $customer = Customer::create($request->all());
        return $this->ok('success', $customer);
    }

    public function show(int $id): JsonResponse
    {
        $customer = Customer::isActive()->whereId($id)->firstOrFail();
        return $this->ok('success', $customer);
    }
   
    public function showByPhone(Request $request): JsonResponse
    {
        $data = Customer::where('phone', (string)$request->phone)->firstOrFail();
        return $this->ok('success', $data);
    }

    public function update(Update $request, Customer $customer): JsonResponse
    {
        $customer->update($request->all());
        return $this->ok('success', $customer);
    }

    public function destroy(Customer $customer): JsonResponse
    {
        $customer->delete();
        return $this->ok('success', $customer);
    }
}
