<?php

namespace Modules\Queue\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Lib\MyHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Queue\Entities\Queue;

class QueueController extends Controller
{

    public function generate(Request $request): JsonResponse
    {
        $code = (int) substr(Queue::where('outlet_id', Auth::user()->outlet_id)->where('type', $request->type)->today()->latest()->first()->code, 1, 2) + 1;
        return $this->ok("success", ['code' => MyHelper::addLeadingZeros(ucfirst(substr($request->type, 0, 1)) . $code)]);
    }

    public function current(): JsonResponse {
        $currentQueue = [
            'consultation' => Queue::where('outlet_id', Auth::user()->outlet_id)->consultation()->today()->latest()->first(),
            'product' => Queue::where('outlet_id', Auth::user()->outlet_id)->product()->today()->latest()->first(),
            'treatment' => Queue::where('outlet_id', Auth::user()->outlet_id)->treatment()->today()->latest()->first(),
        ];
        return $this->ok("success", $currentQueue);

    }
}
