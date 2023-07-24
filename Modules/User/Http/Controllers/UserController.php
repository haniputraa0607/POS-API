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

class UserController extends Controller
{
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

    public function detailUser(Request $request):mixed
    {
        $data['user'] = Auth::user();
        $data['features'] = Auth::user()->get_features();

        return $this->ok(
            "success get data user",
            $data
        );
    }
}
