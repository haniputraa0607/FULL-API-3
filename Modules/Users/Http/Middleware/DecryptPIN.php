<?php

namespace Modules\Users\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Lib\MyHelper;

class DecryptPIN
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $colname = 'pin')
    {
        if ($request->{$colname.'_encrypt'}) {
            $jsonRequest = $request->json()->all();
            $decrypted = MyHelper::decryptPIN($colname.'_encrypt');
            if (!$decrypted) {
                return response()->json([
                    'status' => 'fail',
                    'messages' => 'Invalid PIN'
                ]);
            }
            $jsonRequest[$colname] = $decrypted;
            $request->json()->replace($jsonRequest);
        }
        return $next($request);
    }
}
