<?php

namespace App\Http\Middleware;

use App\Http\Models\UserFeature;
use Closure;

class UserAgentControl
{
  /**
   * Handle an incoming request.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  \Closure  $next
   * @return mixed
   */
  public function handle($request, Closure $next)
  {
    if (strtolower(env('APP_ENV')) == 'production') {
      if ($request->user()['level'] == "Customer") {
        if (stristr($_SERVER['HTTP_USER_AGENT'], 'iOS') || stristr($_SERVER['HTTP_USER_AGENT'], 'okhttp')) {
          return $next($request);
        } else {
            return response()->json(['error' => 'Unauthenticated action'], 403);
        }
      } else {
        return $next($request);
      }
    } else {
      return $next($request);
    }
  }
}
