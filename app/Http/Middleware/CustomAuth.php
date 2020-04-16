<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use SMartins\PassportMultiauth\Http\Middleware\AddCustomProvider;

use Illuminate\Http\Request;

class CustomAuth extends AddCustomProvider
{
    
    public function handle(Request $request, Closure $next, $guard = null)
    {
        if($request->get('outlet-app')){
            $request->merge(['provider' => 'outlet-app']);
        }elseif($request->get('user-franchise')){
            $request->merge(['provider' => 'user-franchise']);
        }elseif($request->get('quinos')){
            $request->merge(['provider' => 'quinos']);
        }else{
            $request->merge(['provider' => 'users']);
        }
        return parent::handle($request, $next);
    }

}
