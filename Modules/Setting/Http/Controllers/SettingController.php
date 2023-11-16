<?php

namespace Modules\Setting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Models\Setting;
use App\Lib\MyHelper;
use Illuminate\Support\Facades\DB;
use Modules\Setting\Http\Requests\TaxRequest;

class SettingController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        $this->splash_path = "splash/";
    }

    public function uploadImage(Request $request):JsonResponse
    {
        $post = $request->all();

        DB::beginTransaction();
        foreach($post ?? [] as $key => $splash){
            $setting = Setting::where('key', $key.'_apps')->first();
            try{
                $encode = base64_encode(fread(fopen($splash, "r"), filesize($splash)));
            }catch(\Exception $e) {
                DB::rollback();
                return $this->error('Error');
            }

            $originalName = $splash->getClientOriginalName();
            if($originalName == ''){
                $ext = 'png';
            }else{
                $ext = pathinfo($originalName, PATHINFO_EXTENSION);
            }
            $name_image = $key;
            $upload = MyHelper::uploadFile($encode, $this->splash_path, $ext, $name_image);
            if (isset($upload['status']) && $upload['status'] == "success") {
                $upload = $setting->update(['value' => $upload['path']]);
            }else {
                DB::rollback();
                return response()->json([
                    'status'=>'fail',
                    'messages'=>['Gagal menyimpan file']
                ]);
            }
        }
        DB::commit();
        return $this->ok('success', []);
    }

    public function createTax(TaxRequest $request)
    {
        $tax = Setting::where('key', 'tax')->firt();
        if($tax){
            $this->updateTax($request);
        } else {
            $setting = Setting::create([
                'key' => 'tax',
                'value' => $request->percentage/100,
                'value_text' => json_encode($request->all())
            ]);
        }
        return $this->ok("success", $setting);
    }

    public function updateTax(TaxRequest $request)
    {
        $setting = Setting::where('key', 'tax');
        $data_update = [
            'value' => $request->percentage/100,
            'value_text' => json_encode($request->all())
        ];
        $setting->update($data_update);
        return $this->ok("success", $data_update);
    }

    public function deleteEqual(Request $request)
    {
        $setting = Setting::where('key', 'tax')->update([
            'value' => 0,
            'value_taxt' => ''
        ]);
        return $this->ok("success", $setting);
    }
}
