<?php

namespace Modules\Customer\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DateTime;
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
        $customer['birth_date_text'] = date('d F Y', strtotime($customer['birth_date']));
        $customer['birth_date_cencored'] = preg_replace("/[^ ]/", "x", $customer['birth_date_text']);

        $bithdayDate = new DateTime($customer['birth_date']);
        $now = new DateTime();
        $interval = $now->diff($bithdayDate)->y;

        $customer['age']  = $interval.' years';
        $customer['email_cencored'] = substr_replace($customer['email'], str_repeat('x', (strlen($customer['email']) - 6)), 3, (strlen($customer['email']) - 6));
        $customer['phone_cencored'] = substr_replace($customer['phone'], str_repeat('x', (strlen($customer['phone']) - 7)), 4, (strlen($customer['phone']) - 7));

        return $this->ok('success', $customer);
    }

    public function show(int $id): JsonResponse
    {
        $customer = Customer::isActive()->whereId($id)->firstOrFail();
        return $this->ok('success', $customer);
    }

    public function showByPhone(Request $request): JsonResponse
    {
        $data = Customer::where('phone', (string)$request->phone)->first();
        if(!$data){
            return $this->error('Customer not found');
        }
        $data['birth_date_text'] = date('d F Y', strtotime($data['birth_date']));
        $data['birth_date_cencored'] = preg_replace("/[^ ]/", "x", $data['birth_date_text']);

        $bithdayDate = new DateTime($data['birth_date']);
        $now = new DateTime();
        $interval = $now->diff($bithdayDate)->y;

        $data['age']  = $interval.' years';
        $data['email_cencored'] = substr_replace($data['email'], str_repeat('x', (strlen($data['email']) - 6)), 3, (strlen($data['email']) - 6));
        $data['phone_cencored'] = substr_replace($data['phone'], str_repeat('x', (strlen($data['phone']) - 7)), 4, (strlen($data['phone']) - 7));
        return $this->ok('success', $data);
    }

    public function update(Update $request): JsonResponse
    {
        $customer = Customer::where('id', $request->id)->first();
        if(!$customer){
            return $this->error('Customer not found');
        }
        $customer->update($request->all());
        $customer['birth_date_text'] = date('d F Y', strtotime($customer['birth_date']));
        $customer['birth_date_cencored'] = preg_replace("/[^ ]/", "x", $customer['birth_date_text']);

        $bithdayDate = new DateTime($customer['birth_date']);
        $now = new DateTime();
        $interval = $now->diff($bithdayDate)->y;

        $customer['age']  = $interval.' years';
        $customer['email_cencored'] = substr_replace($customer['email'], str_repeat('x', (strlen($customer['email']) - 6)), 3, (strlen($customer['email']) - 6));
        $customer['phone_cencored'] = substr_replace($customer['phone'], str_repeat('x', (strlen($customer['phone']) - 7)), 4, (strlen($customer['phone']) - 7));

        return $this->ok('success', $customer);
    }

    public function destroy(Customer $customer): JsonResponse
    {
        $customer->delete();
        return $this->ok('success', $customer);
    }
}
