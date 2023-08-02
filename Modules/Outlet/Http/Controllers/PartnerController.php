<?php

namespace Modules\Outlet\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Outlet\Entities\Partner;

class PartnerController extends Controller
{
    public function index(Request $request):JsonResponse
    {
        $partner = $request->length ? Partner::paginate($request->length ?? 10) : Partner::get();
        return $this->ok('', $partner);
    }

    public function show(Partner $partner):JsonResponse
    {
        return $this->ok('', $partner);
    }

}
