<?php

namespace Modules\Outlet\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Outlet\Entities\Outlet;
use Modules\Outlet\Entities\BannerClinic;
use Modules\Outlet\Http\Requests\EqualIdOutletRequest;
use Modules\Outlet\Http\Requests\OutletRequest;
use Modules\Outlet\Http\Requests\VerifiedOutletRequest;

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

    public function clinic(Request $request)
    {
        $post = $request->json()->all();
        $paginate = empty($post['pagination_total_row']) ? 8 : $post['pagination_total_row'];
        $date_now = date('l');
        
        $outlets = Outlet::with(['outlet_schedule' => function ($query) use ($date_now) {
            $query->where('day', $date_now)->orderBy('open', 'asc'); // Urutkan jadwal berdasarkan waktu buka
        }])->where(function ($query) use ($post) {
            $activities = ['consultation', 'treatment'];
            foreach ($activities as $activity) {
                $query->orWhereJsonContains('activities', $activity);
            }
        })->paginate($paginate, ['*'], 'page', $post['page']);
        $outlet_data = [];
        foreach ($outlets as $outlet) {
            $outlet->coordinates = json_decode($outlet->coordinates);
            $outlet->activities = json_decode($outlet->activities);
            $outlet->images = url(json_decode($outlet->images));
            if ($outlet->outlet_schedule->isNotEmpty()) {
                $currentHour = date('H:i:s');
                $openingHour = $outlet->outlet_schedule[0]->open;
                $closingHour = $outlet->outlet_schedule[0]->close;
                if ($currentHour >= $openingHour && $currentHour < $closingHour) {
                    $status_schedule = 'Open';
                } else {
                    $status_schedule = 'Close';
                }
                $openingHour = date("h:i A", strtotime($openingHour));
                $closingHour = date("h:i A", strtotime($closingHour));
                $outlet_schedule = [
                    'status' => $status_schedule,
                    'open_time' => $openingHour,
                    'close_time' => $closingHour,
                ];
            } else {
                $outlet_schedule = [];
            }
            $outlet->schedule_now = $outlet_schedule;
            // unset($outlet->outlet_schedule);
        }
        return $this->ok('success', $outlets);
    }


    public function clinic_detail($id):JsonResponse
    {
        $outlets = Outlet::with('outlet_schedule')->where('id', $id)->get();
        foreach ($outlets as $outlet) {
            $outlet->coordinates = json_decode($outlet->coordinates);
            $outlet->activities = json_decode($outlet->activities);
            $outlet->images = url(json_decode($outlet->images));
            $outlet_schedule = [];
            if ($outlet->outlet_schedule->isNotEmpty()) {
                $currentHour = date('H:i:s');
                $openingHour = $outlet->outlet_schedule[0]->open;
                $closingHour = $outlet->outlet_schedule[0]->close;
                if ($currentHour >= $openingHour && $currentHour < $closingHour) {
                    $status_schedule = 'Open';
                } else {
                    $status_schedule = 'Close';
                }
                $openingHour = date("h:i A", strtotime($openingHour));
                $closingHour = date("h:i A", strtotime($closingHour));
                $outlet_schedule = [
                    'status' => $status_schedule,
                    'open_time' => $openingHour,
                    'close_time' => $closingHour,
                ];
            }
            $outlet->schedule_now = $outlet_schedule;
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

    public function getVerifiedOutlet($equal_id)
    {
        $outlet = Outlet::where('equal_id', $equal_id)->firstOrFail();
        return $this->ok('success', $outlet);
    }

    public function setVerifiedOutlet(VerifiedOutletRequest $request)
    {
        $outlet = Outlet::where('equal_id', $request->equal_id)->firstOrFail();
        $outlet->update([
            "verified_at" => $request->verified_at
        ]);
        return $this->ok('success', $outlet);
    }

    public function allOutlet(){
        $outlet = Outlet::all();
        return $this->ok("success", $outlet);
    }

    public function setEqualIdOutlet(EqualIdOutletRequest $request){
        $outlet = Outlet::where('id', $request->id)->firstOrFail();
        $outlet->update([
            'equal_id' => $request->equal_id
        ]);
        return $this->ok("success", $outlet);
    }

}
