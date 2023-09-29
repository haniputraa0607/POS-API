<?php

namespace Modules\Outlet\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Outlet\Entities\Outlet;
use Modules\Outlet\Entities\BannerClinic;
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

    public function clinic(Request $request){

        $post = $request->json()->all();
        $paginate = empty($post['pagination_total_row']) ? 8 : $post['pagination_total_row'];
        $outlets = Outlet::where(function ($query) use ($post) {
            $activities = ['consultation', 'treatment'];
            foreach ($activities as $activity) {
                $query->orWhereJsonContains('activities', $activity);
            }
        })->paginate($paginate, ['*'], 'page', $post['page']);
        foreach ($outlets as $outlet) {
            $outlet->coordinates = json_decode($outlet->coordinates);
            $outlet->activities = json_decode($outlet->activities);
            $outlet->images = url(json_decode($outlet->images));
        }
        return $this->ok('success', $outlets);
    }

    public function clinic_detail($id):JsonResponse
    {
        $outlets = Outlet::find($id)->get();
        foreach ($outlets as $outlet) {
            $outlet->coordinates = json_decode($outlet->coordinates);
            $outlet->activities = json_decode($outlet->activities);
            $outlet->images = url(json_decode($outlet->images));
        }

        return $this->ok('success', $outlet);
    }

    public function banner_clinic()
    {
        $banners = BannerClinic::all();
        foreach($banners as $banner){
            $banner->image = url($banner->image);
        }
        return $this->ok("success", $banners);
    }

}
