<?php

namespace Modules\IPay88\Http\Middleware;

use Modules\IPay88\Lib\IPay88;

use Closure;
use Illuminate\Http\Request;

class ValidateSignature
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
        $string = (IPay88::create()->merchant_key).$request->MerchantCode.$request->PaymentId.$request->RefNo.$request->Amount.$request->Currency.$request->Status;
        $hex2bin = function($hexSource){
            $bin = '';
            for ($i=0;$i<strlen($hexSource);$i=$i+2){
                $bin .= chr(hexdec(substr($hexSource,$i,2)));
            }
            return $bin;
        };
        $signature = base64_encode($hex2bin(sha1($string)));
        if ($signature == $request->Signature) {
            return $next($request);
        } else {
            return response()->json([
                'status'   => 'fail',
                'messages' => ['Signature mismatch'],
            ], 401);
        }
    }
}
