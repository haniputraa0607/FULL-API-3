<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\Passport;
use SMartins\PassportMultiauth\Http\Middleware\AddCustomProvider;

class CheckScopes extends AddCustomProvider
{
  /**
   * Handle an incoming request.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  \Closure  $next
   * @return mixed
   */
      public function handle($request, Closure $next, $scope = null)
      {
            $dataToken = json_decode($request->user()->token());
            if($scope == 'ap' && $dataToken->scopes[0] == 'ap'){
                return $next($request);
            }elseif($scope == '*' && $dataToken->scopes[0] == '*'){
                return $next($request);
            }else{
                return response('Unauthenticated.', 403);
            }
      }
}
