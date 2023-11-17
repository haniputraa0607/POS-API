<?php

namespace Modules\Discount\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
// use Illuminate\Routing\Controller;
use App\Http\Controllers\Controller;
use Modules\Discount\Entities\Discount;
use Modules\Discount\Http\Requests\SetEqualIdDisctountRequest;
use Modules\Discount\Http\Requests\VerifiedDiscountRequest;

class DiscountController extends Controller
{
    public function allDiscount()
    {
        $discount = Discount::with('product', 'consultation', 'outlet')->get();
        return $this->ok("success", $discount);
    }

    public function setEqualIdDiscount(SetEqualIdDisctountRequest $request)
    {
        $discount = Discount::with('product', 'consultation', 'outlet')->where('id', $request->id)->firstOrFail();
        $discount->update([
            'equal_id' => $request->equal_id
        ]);
        return $this->ok("success", $discount);
    }

    public function getVerifiedDiscount($equal_id){
        $discount = Discount::with('product', 'consultation', 'outlet')->where('equal_id', $equal_id)->firstOrFail();
        return $this->ok("success", $discount);
    }

    public function setVerifiedDiscount(VerifiedDiscountRequest $request)
    {
        $discount = Discount::with('product', 'consultation', 'outlet')->where('equal_id', $request->equal_id)->firstOrFail();
        $discount->update([
            'verified_at' => $request->verified_at
        ]);
        return $this->ok("success", $discount);
    }
}
