<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Models\Setting;
use App\Http\Models\OauthAccessToken;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\Passport;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Encoding\JoseEncoder;

class CheckScopes
{
    use ApiResponse;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next, $scope = null, $scope2 = null): Response
    {
        $mtScope = ['be', 'pos', 'doctor', 'landing-page'];
        if (in_array($scope, $mtScope) || in_array($scope2, $mtScope)) {
            $getMaintenance = Setting::where('key', 'maintenance_mode')->first();
            if ($getMaintenance && $getMaintenance['value'] == 1) {
                $dt = (array)json_decode($getMaintenance['value_text']);
                $message = $dt['message'];
                if ($dt['image'] != "") {
                    $url_image = config('url.storage_url_api') . $dt['image'];
                } else {
                    $url_image = config('url.storage_url_api') . 'img/maintenance/default.png';
                }
                return response()->json([
                    'status' => 'fail',
                    'messages' => [$message],
                    'maintenance' => config('url.api_url') . "api/maintenance-mode",
                    'data_maintenance' => [
                        'url_image' => $url_image,
                        'text' => $message
                    ]
                ], 200);
            }
        }

        if ($request->user()) {
            $dataToken = json_decode($request->user()->token());
            $scopeUser = $dataToken->scopes[0];
        } else {
            try {
                $bearerToken = $request->bearerToken();

                $parser = new Parser(new JoseEncoder());
                $token = $parser->parse($bearerToken);
                $tokenId = $token->claims()->get('jti');

                $getOauth = OauthAccessToken::find($tokenId);
                $scopeUser = str_replace(str_split('[]""'), "", $getOauth['scopes']);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Unauthenticated.'], 401);
            }
        }

        $arrScope = ['be', 'pos', 'doctor', 'landing-page'];
        if (
            (in_array($scope, $arrScope) && $scope == $scopeUser) ||
            (in_array($scope2, $arrScope) && $scope2 == $scopeUser)
        ) {
            return $next($request);
        }

        return $this->unauthorized("Unauthenticated.");
    }
}
