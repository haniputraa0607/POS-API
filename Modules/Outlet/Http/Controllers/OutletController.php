<?php

namespace Modules\Outlet\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Outlet\Entities\Outlet;
use Modules\Outlet\Http\Requests\OutletRequest;

class OutletController extends Controller
{
    public function index(Request $request):JsonResponse
    {
        $outlet = $request->length ? Outlet::with('district')->paginate($request->length ?? 10) : Outlet::with('district')->get();
        return $this->ok('', $outlet);
    }

    public function store(OutletRequest $request):JsonResponse
    {
        return $this->ok("success create outlet", Outlet::create($request->all()));
    }

    public function show(Outlet $outlet):JsonResponse
    {
        return $this->ok('', $outlet);
    }

    public function update(OutletRequest $request, Outlet $outlet):JsonResponse
    {
        return $this->ok("success update outlet", $outlet->update($request->all()));
    }

    public function destroy(Outlet $outlet):JsonResponse
    {
        $outlet->delete();
        return $this->ok("success delete outlet", $outlet);
    }

    public function activities():JsonResponse
    {
        return $this->ok("success get data all outlet activities", config('outlet_activities'));
    }

}
