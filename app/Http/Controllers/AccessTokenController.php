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
    public function issueToken(ServerRequestInterface $request)
    {
        // return response()->json($request->getParsedBody());
        try {
            if (isset($request->getParsedBody()['username']) && isset($request->getParsedBody()['password'])) {
                if (Auth::attempt(['phone' => $request->getParsedBody()['username'], 'password' => $request->getParsedBody()['password']])) {
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
                            if ($request->getParsedBody()['scope'] == 'doctor' && strtolower($user->type) == 'cashier') {
                                return response()->json(['status' => 'fail', 'messages' => "You don't have access in this app"]);
                            }
                        } else {
                            return response()->json(['status' => 'fail', 'messages' => 'Incompleted input']);
                        }
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

    public function loginCashier(LoginCashierRequest $request): JsonResponse
    {
        Auth::loginUsingId(User::cashier()->isActive()->where('username', $request->username)->firstOrFail()->id);
        $token = auth()->user()->createToken('CashierToken')->accessToken;
        $data = ['user' => auth()->user()->load('outlet.district'), 'token' => $token];
        return $this->ok("success login cashier", $data);
    }
    public function loginDoctor(LoginDoctorRequest $request): JsonResponse
    {
        Auth::loginUsingId(User::doctor()->isActive()->where('username', $request->username)->firstOrFail()->id);
        $token = auth()->user()->createToken('CashierToken')->accessToken;
        $data = ['user' => auth()->user()->load('outlet.district'), 'token' => $token];
        return $this->ok("success login cashier", $data);
    }

    public function logout(): JsonResponse
    {
        Auth::user()->token()->revoke();
        return $this->ok("success logout", []);
    }
}
