<?php

namespace Modules\Outlet\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\Outlet\Entities\Outlet;
use Modules\Outlet\Http\Requests\OutletRequest;

class OutletController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(Request $request)
    {
        $outlet = $request->length ? Outlet::paginate($request->length ?? 10) : Outlet::get();
        return $this->ok('', $outlet);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(OutletRequest $request)
    {
        return $this->ok("success create outlet", Outlet::create($request->all()));
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show(Outlet $outlet)
    {
        return $this->ok('', $outlet);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(OutletRequest $request, Outlet $outlet)
    {
        return $this->ok("success update outlet", $outlet->update($request->all()));
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy(Outlet $outlet)
    {
        $outlet->delete();
        return $this->ok("success delete outlet", $outlet);
    }

    public function activities()
    {
        return $this->ok("success get data all outlet activities", config('outlet_activities'));
    }
}
