<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Models\LogActivitiesDoctorApp;
use Auth;
use App\Lib\MyHelper;
use Modules\User\Entities\User;

class LogActivitiesDoctorAppMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $arrReq = $request->except('_token');

        $url = $request->url();
        if(stristr($url, 'login/cashier')){
            $user = User::doctor()->isActive()->where('username', $arrReq['username'])->first();
            if(!$user){
                return $response;
            }
        }else{
            $user =$request->user();
            if(!$user){
                $user = auth('api')->user();
            }
        }

        $st = stristr(json_encode($response),'success');
        $status = 'fail';
        if($st) $status = 'success';

        $reqnya = $request->json()->all();
        $requestnya = json_encode($reqnya);
        $requeste = json_decode($requestnya, true);

        //subject && module
        $module = null;
        $subject = null;
        if(stristr($url, 'login/doctor')){
            $module = 'Login';
        }elseif(stristr($url, 'logout/doctor')){
            $module = 'Logout';
        }elseif(stristr($url, 'doctor/home')){
            $module = 'Home';
        }elseif(stristr($url, 'doctor/next')){
            $module = 'Next Queue';
        }elseif(stristr($url, 'doctor/prescription')){
            $module = 'Prescription';
            if(stristr($url, 'custom/create')){
                $subject = 'Create';
            }elseif(stristr($url, 'custom/create')){
                $subject = 'Submit';
            }
        }elseif(stristr($url, 'pos/order')){
            $module = 'Order';
            if(stristr($url, 'submit')){
                $subject = 'Submit';
            }else{
                $subject = 'Get';
            }
        }

        if(!empty($request->header('ip-address-view'))){
            $ip = $request->header('ip-address-view');
        }else{
            $ip = $request->ip();
        }

        $userAgent = $request->header('user-agent');

        if($requestnya == '[]') $requestnya = null;
        $data = [
            'module' 	      => ucwords($module),
            'subject' 		  => $subject,
            'url' 			  => $url,
            'phone' 		  => $user['phone'],
            'request' 		  => $requestnya,
            'response_status' => $status,
            'response'        => json_encode($response->original),
            'ip'              => $ip,
            'useragent'       => $userAgent
        ];

        try {
            $log = LogActivitiesDoctorApp::create($data);
        } catch (\Exception $e) {

        }

        return $response;

    }
}
