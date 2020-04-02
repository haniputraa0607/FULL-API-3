<?php

namespace Modules\IPay88\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\IPay88\Entities\LogIpay88;
use Illuminate\Support\Facades\Log;

class LogNotifIpay88
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $head = json_encode($request->header());
        $req = json_encode($request->all());
        $response = $next($request);
        $url = $request->url();
        $toLog = [
            'type' => $request->route('type').'_notif',
            'triggers' => (strpos($url,'detail')!==false)?'user':'backend',
            'id_reference' => $request->RefNo,
            'request' => $req,
            'request_header' => $head,
            'request_url' => $url,
            'response' => json_encode($response)
        ];
        try{
            LogIpay88::create($toLog);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
        return $response;
    }
}
