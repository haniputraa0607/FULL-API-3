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
    public function handle(Request $request, Closure $next, $colname = 'pin', $column = 'phone')
    {
        if ($column == 'request') {
            $column = 'user_phone';
            $request->user_phone = $request->user()->phone;
        }

        if ($request->{$colname.'_encrypt'} && !$request->$colname) {
            $jsonRequest = $request->all();
            $decrypted = MyHelper::decryptPIN($request->{$colname.'_encrypt'}, $request->$column);
            if (!$decrypted) {
                return response()->json([
                    'status' => 'fail',
                    'messages' => ['Invalid PIN']
                ]);
            }
            $jsonRequest[$colname] = $decrypted;
            $request->replace($jsonRequest);
        }
        return $next($request);
    }
}
