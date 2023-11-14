<?php

namespace Modules\User\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\User\Entities\User;
use Modules\User\Http\Requests\UserRequest;
use App\Http\Models\Feature;
use Modules\User\Entities\Admin;
use App\Lib\MyHelper;
use Illuminate\Support\Facades\DB;
use Modules\User\Http\Requests\SetIDUserRequest;
use Modules\User\Http\Requests\SetVerifiedRequest;

class UserController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        $this->user_path = "img/user/";
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request):JsonResponse
    {
        $user = $request->length ?  User::display()->paginate($request->length ?? 10) : User::display()->get();
        return $this->ok("success get data all users", $user);
    }
    /**
     * Display a listing of the resource.
     */
    public function doctor(Request $request):JsonResponse
    {
        return $this->ok(
            "success get data all doctor",
            User::when($request->search, fn ($query, $search) => $query->where('name', 'like', '%' . $search . '%'))
                ->display()
                ->doctor()
                ->paginate(5)
        );
    }
    /**
     * Display a listing of the resource.
     */
    public function cashier(Request $request):JsonResponse
    {
        return $this->ok(
            "success get data all cashier",
            User::when($request->search, fn ($query, $search) => $query->where('name', 'like', '%' . $search . '%'))
                ->display()
                ->cashier()
                ->paginate(5)
        );
    }

    public function detailUser(Request $request):JsonResponse
    {
        $data['user'] = Auth::user();
        $data['features'] = Auth::user()->get_features();

        return $this->ok(
            "success get data user",
            $data
        );
    }

    public function uploadImage(Request $request):JsonResponse
    {
        $post = $request->all();

        $doctor = User::where('id', $post['id_user'])->first();
        DB::beginTransaction();
        try{
            $encode = base64_encode(fread(fopen($post['image'], "r"), filesize($post['image'])));
        }catch(\Exception $e) {
            DB::rollback();
            return $this->error('Error');
        }
        $originalName = $post['image']->getClientOriginalName();
        if($originalName == ''){
            $ext = 'png';
        }else{
            $ext = pathinfo($originalName, PATHINFO_EXTENSION);
        }
        $name_image = str_replace(' ', '_',strtolower($doctor['name']??''));
        $upload = MyHelper::uploadFile($encode, $this->user_path, $ext, $name_image);
        if (isset($upload['status']) && $upload['status'] == "success") {
            $upload = $doctor->update(['image_url' => $upload['path']]);
        }else {
            DB::rollback();
            return response()->json([
                'status'=>'fail',
                'messages'=>['Gagal menyimpan file']
            ]);
        }
        DB::commit();
        return $this->ok('success', $upload);
    }

    public function allUser(){
        $user = User::whereNot('type', 'admin')->get();
        return $this->ok("success", $user);
    }

    public function setEqualIdUser(SetIDUserRequest $request)
    {
        $user = User::where("id", $request->id)->firstOrFail();
        $user->update(["equal_id" => $request->equal_id]);
        return $this->ok("success", $user);
    }

    public function getVerifiedUser($equal_id)
    {
        $user = User::where("equal_id", $equal_id)->firstOrFail();
        return $this->ok("success", $user);
    }

    public function setVerifiedUser(SetVerifiedRequest $request)
    {
        $user = User::where('equal_id', $request->equal_id)->firstOrFail();
        $user->update(["equal_verified_at" => $request->verified_at]);
        return $this->ok("success", $user);
    }
}
