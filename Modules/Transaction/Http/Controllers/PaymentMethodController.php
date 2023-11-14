<?php

namespace Modules\Transaction\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\Transaction\Entities\PaymentMethod;

class PaymentMethodController extends Controller
{

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function all()
    {
        $paymentMethod = PaymentMethod::all();
        return $this->ok("success", $paymentMethod);
    }

    public function setEqualID(Request $request)
    {
        $paymentMethod = PaymentMethod::where('id', $request->id)->firstOrFail();
        $paymentMethod->update(["equal_id" => $request->equal_id]);
        return $this->ok("success", $paymentMethod);
    }

    public function getVerified($equal_id)
    {
        $paymentMethod = PaymentMethod::where('equal_id', $equal_id)->firstOrFail();
        return $this->ok("success", $paymentMethod);
    }

    public function setVerified(Request $request)
    {
        $paymentMethod = PaymentMethod::where('equal_id', $request->equal_id)->firstOrFail();
        $paymentMethod->update(["verified_at" => $request->verified_at]);
        return $this->ok("success", $paymentMethod);
    }
}
