<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Models\LogActivitiesPosApp;
use Auth;
use App\Lib\MyHelper;
use Modules\User\Entities\User;

class LogActivitiesPosAppMiddleware
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
            $user = User::cashier()->isActive()->where('username', $arrReq['username'])->first();
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

        $reqnya = $request->json()->all() ?? null;
        $requestnya = json_encode($reqnya);
        $requeste = json_decode($requestnya, true);

        //subject && module
        $module = null;
        $subject = null;
        if(stristr($url, 'login/cashier')){
            $module = 'Login';
        }elseif(stristr($url, 'logout/cashier')){
            $module = 'Logout';
        }elseif(stristr($url, 'pos/order')){
            $module = 'Order';
            if(stristr($url, 'submit')){
                $subject = 'Submit';
            }elseif(stristr($url, 'save')){
                $subject = 'Save';
            }elseif(stristr($url, 'ticket')){
                $subject = 'Ticket';
            }elseif(stristr($url, 'list')){
                $subject = 'List';
            }elseif(stristr($url, 'detail')){
                $subject = 'Detail';
            }elseif(stristr($url, 'detail/delete')){
                $subject = 'Delete';
            }else{
                $subject = 'Get';
            }
        }elseif(stristr($url, 'pos/transaction')){
            $module = 'Transaction';
            if(stristr($url, 'confirm')){
                $subject = 'Confirm';
            }elseif(stristr($url, 'done')){
                $subject = 'Detail';
            }
        }elseif(stristr($url, 'pos/cashier')){
            $module = 'Cashier';
            if(stristr($url, 'histories')){
                $subject = 'Login History';
            }elseif(stristr($url, 'all-schedule')){
                $subject = 'All Schedule';
            }elseif(stristr($url, 'record-trx')){
                $subject = 'Transaction Record';
            }elseif(stristr($url, 'customer/draft')){
                $subject = 'Draft';
            }elseif(stristr($url, 'customer/treatment')){
                $subject = 'Treatment';
            }elseif(stristr($url, 'customer/consultation')){
                $subject = 'Consultation';
            }else{
                $subject = 'Update Profile';
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
            $log = LogActivitiesPosApp::create($data);
        } catch (\Exception $e) {

        }

        return $response;

    }
}
