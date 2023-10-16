<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginCashierRequest;
use App\Http\Requests\LoginCmsRequest;
use App\Http\Requests\LoginDoctorRequest;
use App\Traits\ApiResponse;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\JsonResponse;
use Laravel\Passport\Http\Controllers\AccessTokenController as PassportAccessTokenController;
use League\OAuth2\Server\Exception\OAuthServerException;
use Psr\Http\Message\ServerRequestInterface;
use Nyholm\Psr7\Response as Psr7Response;
use Illuminate\Support\Facades\Auth;
use Modules\User\Entities\User;
use Illuminate\Http\Request;
use App\Http\Models\OauthClient;
use Modules\EmployeeSchedule\Entities\EmployeeScheduleDate;
use Modules\Cashier\Entities\EmployeeAttendance;
use Modules\Outlet\Entities\OutletDevice;

class AccessTokenController extends PassportAccessTokenController
{
    use ApiResponse;

    /**
     * Authorize a client to access the user's account.
     *
     * @param  ServerRequestInterface $request
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \League\OAuth2\Server\Exception\OAuthServerException
     */

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function issueToken(ServerRequestInterface $request)
    {

        try {
            if (isset($request->getParsedBody()['username'])) {
                if (Auth::attempt(['phone' => $request->getParsedBody()['username'], 'password' => '777777'])) {
                    $user = User::where('phone', $request->getParsedBody()['username'])->first();
                    if ($user) {
                        //check if user already suspended
                        // if($user->is_suspended == '1'){
                        //     return response()->json(['status' => 'fail', 'messages' => 'Akun Anda telah diblokir karena menunjukkan aktivitas mencurigakan. Untuk informasi lebih lanjut harap hubungi customer service kami.']);
                        // }

                        // //check if otp have expired and the current time exceeds the expiration time
                        // if(!empty($user->otp_forgot) && !is_null($user->otp_valid_time) && strtotime(date('Y-m-d H:i:s')) > strtotime($user->otp_valid_time)){
                        //     return response()->json(['status' => 'fail', 'messages' => 'This OTP is expired, please re-request OTP from apps']);
                        // }

                        if (isset($request->getParsedBody()['scope'])) {
                            if ($request->getParsedBody()['scope'] == 'doctor' && strtolower($user->type) != 'salesman') {
                                return response()->json(['status' => 'fail', 'messages' => "You don't have access in this app"]);
                            }
                            if ($request->getParsedBody()['scope'] == 'pos' && strtolower($user->type) != 'cashier') {
                                return response()->json(['status' => 'fail', 'messages' => "You don't have access in this app"]);
                            }
                            if ($request->getParsedBody()['scope'] == 'be' && strtolower($user->type) != 'admin') {
                                return response()->json(['status' => 'fail', 'messages' => "You don't have access in this app"]);
                            }
                        } else {
                            return response()->json(['status' => 'fail', 'messages' => 'Incompleted input']);
                        }
                    } else {
                        return response()->json(['status' => 'fail', 'messages' => 'Usermame atau pin tidak sesuai.']);
                    }
                } else {
                    return response()->json(['status' => 'fail', 'messages' => 'Usermame atau pin tidak sesuai.']);
                }
            }

            return $this->convertResponse(
                $this->server->respondToAccessTokenRequest($request, new Psr7Response())
            );
        } catch (OAuthServerException $exception) {
            //return error message

            if ($exception->getCode() == 6) {
                return response()->json(['status' => 'fail', 'messages' => 'Pin tidak sesuai.']);
            }

            return $this->withErrorHandling(function () use ($exception) {
                throw $exception;
            });
        }
    }

    public function loginCMS(LoginCmsRequest $request): JsonResponse
    {
        Auth::attempt($request->all());
        $token = auth()->user()->createToken('API Token')->accessToken;
        $data = ['user' => auth()->user()->load('outlet.district'), 'token' => $token];

        return $this->ok("success login cms", $data);
    }

    public function loginCashier(LoginCashierRequest $request): mixed
    {
        $post = $request->json()->all();

        if ($post['scope'] != 'pos') {
            return $this->error('Scope invalid');
        }
        $passport = OauthClient::where('id', $post['client_id'])->where('secret', $post['client_secret'])->first();
        if (!$passport) {
            return $this->error('Client Secret not found');
        }
        $user = User::cashier()->isActive()->where('username', $post['username'])->first();
        if (!$user) {
            return $this->error('User not found');
        }
        Auth::loginUsingId($user['id']);
        $token = auth()->user()->createToken('CashierToken', ['pos'])->accessToken;
        $data = ['access_token' => $token, 'token_type' => 'Bearer'];
        $device = OutletDevice::whereDate('date', date('Y-m-d'))->where('outlet_id', $user['outlet_id'])->get()->toArray();
        if($device){
            $no = count($device) + 1;

            $check = array_search($post['device_id'], array_column($device??[], 'device_id'));
            if($check !== false){
                $device = $device[$check];
            }else{
                $device = OutletDevice::create([
                    'outlet_id' => $user['outlet_id'],
                    'date' => date('Y-m-d'),
                    'name' => 'Cashier '.$no,
                    'device_id' => $post['device_id']
                ]);
            }
        }else{
            $device = OutletDevice::create([
                'outlet_id' => $user['outlet_id'],
                'date' => date('Y-m-d'),
                'name' => 'Cashier 1',
                'device_id' => $post['device_id']
            ]);
        }


        $attendance = EmployeeAttendance::where('user_id', $user['id'])->whereDate('date', date('Y-m-d'))->first();
        if(!$attendance){
            $schedule_date = EmployeeScheduleDate::whereHas('employee_schedule', function($schedule) use($user){
                $schedule->where('user_id', $user['id'])->where('schedule_month', date('m'))->where('schedule_year', date('Y'));
            })->whereDate('date', date('Y-m-d'))->first();

            if(!isset($post['device_id'])){
                return $this->error('Device ID not found');
            }

            $attendance = EmployeeAttendance::create([
                'user_id' => $user['id'],
                'date' => date('Y-m-d'),
                'employee_schedule_date_id' => $schedule_date['id'] ?? null,
                'attendance_time' => date('H:i:s'),
                'outlet_device_id' => $device['id']
            ]);
        }elseif($attendance && $attendance['outlet_device_id'] != $device['id']){
            $attendance_update = $attendance->update([
                'outlet_device_id' => $device['id']
            ]);
        }

        return $this->ok("success login cashier", $data);
    }
    public function loginDoctor(LoginDoctorRequest $request): JsonResponse
    {
        $post = $request->json()->all();

        if ($post['scope'] != 'doctor') {
            return $this->error('Scope invalid');
        }

        $passport = OauthClient::where('id', $post['client_id'])->where('secret', $post['client_secret'])->first();
        if (!$passport) {
            return $this->error('Client Secret not found');
        }
        $user = User::doctor()->isActive()->where('username', $post['username'])->first();
        if (!$user) {
            return $this->error('User not found');
        }
        Auth::loginUsingId($user['id']);
        $token = auth()->user()->createToken('DoctorToken', ['doctor'])->accessToken;
        $data = ['access_token' => $token, 'token_type' => 'Bearer'];
        return $this->ok("success login doctor", $data);
    }

    public function logout(): JsonResponse
    {
        Auth::user()->token()->revoke();
        return $this->ok("success logout", []);
    }
}
