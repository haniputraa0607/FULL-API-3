<?php

namespace App\Http\Middleware;

use App\Http\Models\UserFeature;
use Closure;

class FeatureControl
{
  /**
   * Handle an incoming request.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  \Closure  $next
   * @return mixed
   */
  public function handle($request, Closure $next, $feature, $feature2 = null)
  {
    if ($request->user()['level'] == "Super Admin") return $next($request);

    $granted = UserFeature::where('id_user', $request->user()['id'])->where('id_feature', $feature)->first();
    if (!$granted) {
        return response('Unauthorized action.', 403);
    } else {
      $next($request);
    }
  }
}
