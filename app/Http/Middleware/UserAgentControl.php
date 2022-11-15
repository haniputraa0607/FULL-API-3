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
        return $next($request);
    }
}
