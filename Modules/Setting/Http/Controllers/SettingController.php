<?php

namespace Modules\Setting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Models\Setting;
use App\Lib\MyHelper;
use Illuminate\Support\Facades\DB;

class SettingController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        $this->splash_path = "splash/";
    }

    public function uploadImage(Request $request):mixed
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
}
