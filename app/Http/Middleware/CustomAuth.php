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
        }else{
            $request->merge(['provider' => 'users']);
        }
        return parent::handle($request, $next);
    }

}
