<?php

namespace Modules\User\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\User\Entities\User;
use Modules\User\Http\Requests\UserRequest;

class UserController extends Controller
{
      /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->length ?  User::display()->paginate($request->length ?? 10) : User::display()->get();
        return $this->ok("success get data all users", $user);
    }
    /**
     * Display a listing of the resource.
     */
    public function doctor()
    {
        return $this->ok("success get data all doctor", User::display()->doctor()->paginate(5));
    }
    /**
     * Display a listing of the resource.
     */
    public function cashier()
    {
        return $this->ok("success get data all cashier", User::display()->cashier()->paginate(5));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserRequest $request)
    {
        return $this->ok("success create user", User::create($request->all()));
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return $this->ok("success get data all users", $user);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UserRequest $request, User $user)
    {
        $user->update($request->all());
        return $this->ok("success update user", $user);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $user->delete();
        return $this->ok("success delete user", $user);
    }

}
