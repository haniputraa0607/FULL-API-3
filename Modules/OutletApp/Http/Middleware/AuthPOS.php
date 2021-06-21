<?php

namespace Modules\OutletApp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Http\Models\Outlet;
use App\Http\Models\Setting;

class AuthPOS
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
        $api_key = Setting::where('key', 'api_key')->pluck('value')->first();
        $api_secret = Setting::where('key', 'api_secret')->pluck('value')->first();
        if ($api_key != $request->api_key || $api_secret != $request->api_secret) {
            return response([
                'status' => 'fail',
                'messages' => ['invalid api_key and api_secret combination']
            ], 401);
        }

        $outlet = Outlet::where('outlet_code', $request->store_code)->first();
        if (!$outlet) {
            return response([
                'status' => 'fail',
                'messages' => [
                    'Invalid store code'
                ]
            ]);
        }
        $toMerge = [
            'user' => $outlet
        ];
        $request->merge($toMerge)
            ->setUserResolver(function () use ($outlet) {
                return $outlet;
            });
        return $next($request);
    }
}
